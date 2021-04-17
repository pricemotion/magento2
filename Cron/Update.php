<?php
namespace Pricemotion\Magento2\Cron;

use InvalidArgumentException;
use Magento\Catalog\Api\Data\CostInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Action;
use Magento\Framework\App\Area;
use Magento\Framework\Indexer\AbstractProcessor;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\ConfigurationException;
use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\App\EAN;
use Pricemotion\Magento2\App\PricemotionClient;
use Pricemotion\Magento2\App\PriceRule;
use Pricemotion\Magento2\App\Product as PricemotionProduct;
use Pricemotion\Magento2\Logger\Logger;
use Pricemotion\Magento2\Model\ProductRepository;
use Pricemotion\Magento2\Observer\ProductSave;
use RuntimeException;
use Throwable;

class Update {
    private const UPDATE_INTERVAL = 3600 * 12;

    private const INDEXER_IDS = [
        \Magento\Catalog\Model\Indexer\Product\Price\Processor::INDEXER_ID,
        \Magento\Catalog\Model\Indexer\Product\Eav\Processor::INDEXER_ID,
    ];

    private $logger;

    private $config;

    private $pricemotion;

    private $priceAttribute;

    private $listPriceAttribute;

    private $ignoreUpdatedAt = false;

    private $productAction;

    private $productSaveObserver;

    private $timeLimit = 55;

    private $eanFilter = null;

    private $storeManager;

    private $emulation;

    private $indexerRegistry;

    private $productRepository;

    public function __construct(
        Logger $logger,
        Config $config,
        PricemotionClient $pricemotionClient,
        Action $productAction,
        ProductSave $productSaveObserver,
        StoreManagerInterface $storeManager,
        Emulation $emulation,
        IndexerRegistry $indexerRegistry,
        ProductRepository $productRepository
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->pricemotion = $pricemotionClient;
        $this->productAction = $productAction;
        $this->productSaveObserver = $productSaveObserver;
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
        $this->indexerRegistry = $indexerRegistry;
        $this->productRepository = $productRepository;
    }

    public function setIgnoreUpdatedAt(bool $value): void {
        $this->ignoreUpdatedAt = $value;
    }

    public function setTimeLimit(?int $timeLimit): void {
        $this->timeLimit = $timeLimit;
    }

    public function setEanFilter(?array $eanFilter): void {
        $this->eanFilter = $eanFilter;
    }

    public function execute(): void {
        $default_store = $this->storeManager->getDefaultStoreView();
        if (!$default_store) {
            $this->logger->error('No default store view is configured; aborting');
            return;
        }

        $this->emulation->startEnvironmentEmulation($default_store->getId(), Area::AREA_ADMINHTML);

        try {
            $this->doExecute();
        } catch (Throwable $e) {
            $this->logger->critical(
                sprintf(
                    'Uncaught exception %s: (%d) %s',
                    get_class($e),
                    $e->getCode(),
                    $e->getMessage()
                )
            );
            $this->logger->critical((string) $e);
            throw $e;
        } finally {
            $this->emulation->stopEnvironmentEmulation();
        }
    }

    private function doExecute(): void {
        if ($this->timeLimit === null) {
            $run_until = null;
        } else {
            $run_until = time() + $this->timeLimit;
        }

        try {
            $this->priceAttribute = $this->config->getPriceAttribute();
            $this->listPriceAttribute = $this->config->getListPriceAttribute();

            $this->logger->debug("EAN attribute: {$this->config->requireEanAttribute()}");
            $this->logger->debug("Price attribute: {$this->priceAttribute}");
            $this->logger->debug("List price attribute: {$this->listPriceAttribute}");

            if ($this->eanFilter !== null) {
                $products = $this->productRepository->getByEans($this->eanFilter);
                if ($this->ignoreUpdatedAt) {
                    $this->logger->warning("The `force' option is superfluous when selecting EANs to be updated");
                }
            } elseif ($this->ignoreUpdatedAt) {
                $products = $this->productRepository->getAll();
            } else {
                $products = $this->productRepository->getForUpdate(self::UPDATE_INTERVAL);
            }
        } catch (ConfigurationException $e) {
            $this->logger->warning($e->getMessage());
            return;
        }

        if (!$products) {
            $this->logger->info('There are no products that need updating');
            return;
        }

        $this->logger->info(sprintf('Got %d products for update', sizeof($products)));

        shuffle($products);

        $processed = 0;
        foreach ($products as $product) {
            assert($product instanceof Product);

            $this->logger->debug(sprintf(
                'Product %d: %s',
                $product->getId(),
                json_encode($product->getData(), JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)
            ));

            $this->updateProduct($product);

            $processed++;

            if ($run_until !== null
                && time() > $run_until
            ) {
                $this->logger->info(sprintf('Ran out of time after processing %d products', $processed));
                return;
            }
        }
    }

    private function updateProduct(Product $product): void {
        $update = $this->getUpdateData($product);

        if ($update) {
            $this->logger->info(sprintf(
                'Update product %d: %s',
                $product->getId(),
                json_encode($update, JSON_PARTIAL_OUTPUT_ON_ERROR)
            ));
            $this->productAction->updateAttributes([$product->getId()], $update, $product->getStoreId());
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

    private function getUpdateData(Product $product): array {
        $ean_string = $product->getData($this->config->requireEanAttribute());

        try {
            $ean = EAN::fromString($ean_string);
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
            Constants::ATTR_LOWEST_PRICE => $pricemotion_product->getLowestPrice(),
            Constants::ATTR_UPDATED_AT => microtime(true),
        ];

        if (($new_price = $this->getNewPrice($product, $pricemotion_product)) !== null
            && $this->priceAttribute
        ) {
            $update[$this->priceAttribute] = $new_price;
        }

        foreach ($update as $attr => $value) {
            $product->setData($attr, $value);
        }

        $this->productSaveObserver->setLowestPriceRatio($product);

        if ($product->hasData(Constants::ATTR_LOWEST_PRICE_RATIO)) {
            $update[Constants::ATTR_LOWEST_PRICE_RATIO] = $product->getData(Constants::ATTR_LOWEST_PRICE_RATIO);
        }

        return $update;
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
            $rule = (new PriceRule\Factory())->fromArray($settings);
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

        if (!$this->priceAttribute) {
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
            if (!$this->listPriceAttribute) {
                $this->logger->warning(sprintf(
                    'Maximum list price discount set for product %d, but no list price attribute is configured',
                    $product->getId()
                ));
            } elseif (($list_price = (float) $product->getData($this->listPriceAttribute)) < 0.01) {
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

        if (abs($product->getData($this->priceAttribute) - $new_price) < 0.005) {
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
            (float) $product->getData($this->priceAttribute),
            $new_price,
            get_class($rule)
        ));

        return $new_price;
    }
}
