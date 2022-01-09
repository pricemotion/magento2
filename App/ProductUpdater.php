<?php
namespace Pricemotion\Magento2\App;

use Closure;
use Generator;
use InvalidArgumentException;
use Magento\Catalog\Api\Data\CostInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Indexer\AbstractProcessor;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Mview\View\Changelog;
use Magento\Indexer\Model\Indexer\Collection;
use Pricemotion\Sdk\Data\Ean;
use Pricemotion\Sdk\Data\Product as PricemotionProduct;
use Pricemotion\Magento2\Logger\Logger;
use Pricemotion\Magento2\Model\Attributes;
use Pricemotion\Magento2\Observer\ProductSave;
use RuntimeException;

class ProductUpdater {
    private const INDEXER_IDS = [
        \Magento\Catalog\Model\Indexer\Product\Price\Processor::INDEXER_ID,
        \Magento\Catalog\Model\Indexer\Product\Eav\Processor::INDEXER_ID,
    ];

    private $logger;

    private $config;

    private $pricemotion;

    private $productAction;

    private $productSaveObserver;

    private $indexerRegistry;

    private $priceAttribute;

    private $listPriceAttribute;

    private $indexerCollection;

    public function __construct(
        Logger $logger,
        Config $config,
        PricemotionClient $pricemotion,
        Action $productAction,
        ProductSave $productSaveObserver,
        IndexerRegistry $indexerRegistry,
        Attributes\Price $priceAttribute,
        Attributes\ListPrice $listPriceAttribute,
        Collection $indexerCollection
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->pricemotion = $pricemotion;
        $this->productAction = $productAction;
        $this->productSaveObserver = $productSaveObserver;
        $this->indexerRegistry = $indexerRegistry;
        $this->priceAttribute = $priceAttribute;
        $this->listPriceAttribute = $listPriceAttribute;
        $this->indexerCollection = $indexerCollection;
    }

    public function update(Product $product): void {
        $update = $this->getUpdateData($product);

        if (!$update) {
            return;
        }

        $this->logger->info(sprintf(
            'Update product %d: %s',
            $product->getId(),
            json_encode($update, JSON_PARTIAL_OUTPUT_ON_ERROR)
        ));

        $this->transact($this->productAction->getConnection(), function () use ($product, $update): void {
            $changelogVersions = null;
            if (!$this->isIndexableUpdate($update)) {
                $changelogVersions = $this->getChangelogVersions();
            }

            $this->productAction->updateAttributes(
                [$product->getId()],
                $update,
                $product->getStoreId()
            );

            if ($changelogVersions !== null) {
                $this->revertChangelogs($changelogVersions, $product);
            }

            if ($this->isIndexableUpdate($update)) {
                $this->indexProduct($product);
            }
        });
    }

    private function transact(AdapterInterface $conn, $fn): void {
        $conn->beginTransaction();
        try {
            $fn();
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    private function getChangelogVersions(): array {
        $result = [];
        foreach ($this->getChangelogs() as $changelog) {
            $result[$changelog->getName()] = $changelog->getVersion();
        }
        return $result;
    }

    private function isIndexableUpdate(array $update): bool {
        return !!array_diff(array_keys($update), [Constants::ATTR_UPDATED_AT]);
    }

    private function revertChangelogs(array $previousVersions, Product $product): void {
        foreach ($this->getChangelogs() as $changelog) {
            if (!isset($previousVersions[$changelog->getName()])) {
                continue;
            }
            $this->revertChangelog($changelog, $previousVersions[$changelog->getName()], $product);
        }
    }

    private function revertChangelog(Changelog $changelog, int $previousVersion, Product $product): void {
        /** @phan-closure-scope Changelog */
        $closure = function () use ($changelog): ResourceConnection {
            return $changelog->resource;
        };
        $resource = Closure::bind($closure, null, $changelog)();
        $resource->getConnection()->delete(
            $resource->getTableName($changelog->getName()),
            [
                'version_id > ?' => $previousVersion,
                'entity_id' => $product->getId(),
            ]
        );
    }

    /** @return Generator<Changelog> */
    private function getChangelogs(): Generator {
        foreach ($this->indexerCollection->getItems() as $indexer) {
            $changelog = $indexer->getView()->getChangelog();
            if ($changelog instanceof Changelog) {
                yield $changelog;
            }
        }
    }

    private function getUpdateData(Product $product): array {
        $ean_string = $product->getData($this->config->requireEanAttribute());

        try {
            $ean = Ean::fromString($ean_string);
        } catch (InvalidArgumentException $e) {
            $this->logger->debug(sprintf(
                "Skipping product %d with invalid EAN '%s': %s",
                $product->getId(),
                $ean_string,
                $e->getMessage()
            ));
            return [];
        }

        $this->logger->info(sprintf(
            'Updating product %d with EAN %s',
            $product->getId(),
            $ean->toString()
        ));

        try {
            $pricemotion_product = $this->pricemotion->getProduct($ean);
        } catch (RuntimeException $e) {
            $this->logger->error(sprintf(
                'Could not get Pricemotion data for product %d with EAN %s: %s',
                $product->getId(),
                $ean->toString(),
                $e->getMessage()
            ));
            return [Constants::ATTR_UPDATED_AT => microtime(true)];
        }

        $update = [
            Constants::ATTR_UPDATED_AT => microtime(true),
        ];

        if (!$this->isApproximatelyEqual(
            $product->getData(Constants::ATTR_LOWEST_PRICE),
            $pricemotion_product->getLowestPrice()
        )) {
            $update[Constants::ATTR_LOWEST_PRICE] = $pricemotion_product->getLowestPrice();
        }

        if (($new_price = $this->getNewPrice($product, $pricemotion_product)) !== null
            && ($priceAttribute = $this->priceAttribute->getCode())
        ) {
            $update[$priceAttribute] = $new_price;
        }

        foreach ($update as $attr => $value) {
            $product->setData($attr, $value);
        }

        $previousLowestPriceRatio = $product->getData(Constants::ATTR_LOWEST_PRICE_RATIO);
        $this->productSaveObserver->setLowestPriceRatio($product);
        if (!$this->isApproximatelyEqual(
            $previousLowestPriceRatio,
            $product->getData(Constants::ATTR_LOWEST_PRICE_RATIO)
        )) {
            $update[Constants::ATTR_LOWEST_PRICE_RATIO] = $product->getData(Constants::ATTR_LOWEST_PRICE_RATIO);
        }

        return $update;
    }

    private function isApproximatelyEqual($a, $b): bool {
        if ($a === $b) {
            return true;
        }
        if (is_numeric($a) && is_numeric($b) && abs((float) $a - (float) $b) < 0.00001) {
            return true;
        }
        return false;
    }

    private function getNewPrice(Product $product, PricemotionProduct $pricemotion_product): ?float {
        $settings = $product->getData(Constants::ATTR_SETTINGS);
        if (!$settings) {
            return null;
        }

        $settings = json_decode($settings, true);
        if (!is_array($settings) || !$settings) {
            return null;
        }

        try {
            $rule = (new \Pricemotion\Sdk\PriceRule\Factory())->fromArray($settings);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(sprintf(
                'Invalid price rule for product %d: %s',
                $product->getId(),
                $e->getMessage()
            ));
            return null;
        }

        $new_price = $rule->calculate($pricemotion_product);
        if (!$new_price) {
            return null;
        }

        if (!$this->priceAttribute->getCode()) {
            $this->logger->error(sprintf(
                'Prices rules are configured for product %d, but no price attribute is configured',
                $product->getId()
            ));
            return null;
        }

        if (!empty($settings['protectMargin'])) {
            $minimum_margin = (float) $settings['minimumMargin'];
            $cost = (float) $product->getData(CostInterface::COST);
            if ($cost < 0.01) {
                $this->logger->error(sprintf(
                    'Margin protection enabled, but no cost price found for product %d',
                    $product->getId()
                ));
                return null;
            }
            $minimum_price = round($cost * (1 + $minimum_margin / 100), 2);
            if ($new_price < $minimum_price) {
                $this->logger->info(sprintf(
                    'Using minimum margin protection price %.4f instead of %.4f for product %d (%.4f + %.4f%%)',
                    $minimum_price,
                    $new_price,
                    $product->getId(),
                    $cost,
                    $minimum_margin
                ));
                $new_price = $minimum_price;
            }
        }

        if (!empty($settings['limitListPriceDiscount'])) {
            if (!$this->listPriceAttribute->getCode()) {
                $this->logger->warning(sprintf(
                    'Maximum list price discount set for product %d, but no list price attribute is configured',
                    $product->getId()
                ));
            } elseif (($list_price = $this->listPriceAttribute->get($product)) < 0.01) {
                $this->logger->warning(sprintf(
                    'Maximum list price discount enabled, but no list price found for product %d',
                    $product->getId()
                ));
            } else {
                $maximum_discount = (float) $settings['maximumListPriceDiscount'];
                $minimum_price = round($list_price * (1 - $maximum_discount / 100), 2);
                if ($new_price < $minimum_price) {
                    $this->logger->info(sprintf(
                        'Using maximum list price discount price %.4f instead of %.4f for product %d (%.4f - %.4f%%)',
                        $minimum_price,
                        $new_price,
                        $product->getId(),
                        $list_price,
                        $maximum_discount
                    ));
                    $new_price = $minimum_price;
                }
            }
        }

        if (!empty($settings['roundPrecision']) && ($round_precision = (float) $settings['roundPrecision']) > 0.01) {
            $rounded_price = round($new_price / $round_precision) * $round_precision;
            if (!empty($settings['roundUp'])) {
                if ($rounded_price < $new_price) {
                    $rounded_price += $round_precision;
                }
            } else {
                if ($rounded_price > $new_price) {
                    $rounded_price -= $round_precision;
                }
            }
            $this->logger->info(sprintf(
                'Rounding price %.4f to precision %.4f for product %d: %.4f',
                $new_price,
                $round_precision,
                $product->getId(),
                $rounded_price
            ));
            $new_price = $rounded_price;
        }

        if (abs($this->priceAttribute->get($product) - $new_price) < 0.005) {
            $this->logger->debug(sprintf(
                'Would adjust product %d price to %.2f according to %s, but it is already there',
                $product->getId(),
                $new_price,
                get_class($rule)
            ));
            return null;
        }

        $this->logger->info(sprintf(
            'Adjusting product %d price from %.2f to %.2f according to %s',
            $product->getId(),
            $this->priceAttribute->get($product),
            $new_price,
            get_class($rule)
        ));

        return $new_price;
    }

    private function indexProduct(Product $product): void {
        foreach (self::INDEXER_IDS as $indexer_id) {
            $indexer = $this->indexerRegistry->get($indexer_id);
            if ($indexer instanceof AbstractProcessor) {
                $indexer->reindexRow($product->getId(), true);
            } else {
                /** @phan-suppress-next-line PhanDeprecatedFunction */
                $indexer->reindexRow($product->getId());
            }
        }
    }
}
