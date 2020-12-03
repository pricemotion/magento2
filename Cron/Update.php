<?php
namespace Pricemotion\Magento2\Cron;

use Magento\Catalog\Api\Data\CostInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\App\EAN;
use Pricemotion\Magento2\App\PricemotionClient;
use Pricemotion\Magento2\App\PriceRule;
use Pricemotion\Magento2\App\Product as PricemotionProduct;
use Pricemotion\Magento2\Logger\Logger;
use Pricemotion\Magento2\Observer\ProductSave;

class Update {
    private const UPDATE_INTERVAL = 3600 * 12;

    private $logger;
    private $productCollectionFactory;
    private $config;
    private $pricemotion;
    private $eanAttribute;
    private $priceAttribute;
    private $listPriceAttribute;
    private $ignoreUpdatedAt = false;
    private $productAction;
    private $productSaveObserver;
    private $timeLimit = 55;
    private $eanFilter = null;

    public function __construct(
        Logger $logger,
        CollectionFactory $product_collection_factory,
        Config $config,
        PricemotionClient $pricemotion_client,
        Action $product_action,
        ProductSave $product_save_observer
    ) {
        $this->logger = $logger;
        $this->productCollectionFactory = $product_collection_factory;
        $this->config = $config;
        $this->pricemotion = $pricemotion_client;
        $this->productAction = $product_action;
        $this->productSaveObserver = $product_save_observer;
    }

    public function setIgnoreUpdatedAt(bool $value): void {
        $this->ignoreUpdatedAt = $value;
    }

    public function setTimeLimit(?int $time_limit): void {
        $this->timeLimit = $time_limit;
    }

    public function setEanFilter(?array $eanFilter): void {
        $this->eanFilter = $eanFilter;
    }

    public function execute(): void {
        if ($this->timeLimit === null) {
            $run_until = null;
        } else {
            $run_until = time() + $this->timeLimit;
        }

        $this->eanAttribute = $this->config->getEanAttribute();
        if (!$this->eanAttribute) {
            $this->logger->warning("No EAN product attribute is configured; not updating products");
            return;
        }

        $this->priceAttribute = $this->config->getPriceAttribute();
        $this->listPriceAttribute = $this->config->getListPriceAttribute();

        $product_collection = $this->productCollectionFactory->create();

        $product_collection->addAttributeToFilter($this->eanAttribute, ['neq' => '']);

        if ($this->eanFilter !== null) {
            $product_collection->addAttributeToFilter($this->eanAttribute, array_map(function ($ean) {
                return ['eq' => $ean];
            }, $this->eanFilter));
            if ($this->ignoreUpdatedAt) {
                $this->logger->warning("The `force' option is superfluous when selecting EANs to be updated");
            }
        } elseif (!$this->ignoreUpdatedAt) {
            $product_collection->addAttributeToSelect(Constants::ATTR_UPDATED_AT, 'left');
            $product_collection->addAttributeToFilter(Constants::ATTR_UPDATED_AT, [
                ['null' => true],
                ['lt' => microtime(true) - self::UPDATE_INTERVAL],
            ]);
        }

        $product_collection->addAttributeToSelect($this->eanAttribute);
        if ($this->priceAttribute) {
            $product_collection->addAttributeToSelect($this->priceAttribute);
        }
        if ($this->listPriceAttribute) {
            $product_collection->addAttributeToSelect($this->listPriceAttribute);
        }
        $product_collection->addAttributeToSelect(Constants::ATTR_UPDATED_AT);
        $product_collection->addAttributeToSelect(Constants::ATTR_SETTINGS);
        $product_collection->addAttributeToSelect(CostInterface::COST);

        $product_collection->addPriceData();

        /** @var Product[] $products */
        $products = $product_collection->getItems();

        if (!$products) {
            $this->logger->debug("There are no products that need updating");
            return;
        }

        $this->logger->info(sprintf("Got %d products for update", sizeof($products)));

        shuffle($products);

        $processed = 0;
        foreach ($products as $product) {
            $this->logger->debug(sprintf(
                "Product %d: %s",
                $product->getId(),
                json_encode($product->getData(), JSON_UNESCAPED_UNICODE, JSON_PARTIAL_OUTPUT_ON_ERROR)
            ));

            $this->updateProduct($product);

            $processed++;

            if ($run_until !== null
                && time() > $run_until
            ) {
                $this->logger->info(sprintf("Ran out of time after processing %d products", $processed));
            }
        }
    }

    private function updateProduct(Product $product): void {
        $ean_string = $product->getData($this->eanAttribute);

        try {
            $ean = EAN::fromString($ean_string);
        } catch (\InvalidArgumentException $e) {
            $this->logger->debug(sprintf(
                "Skipping product %d with invalid EAN '%s': %s",
                $product->getId(), $ean_string, $e->getMessage()
            ));
            return;
        }

        $this->logger->info(sprintf(
            "Updating product %d with EAN %s",
            $product->getId(), $ean->toString()
        ));

        try {
            $pricemotion_product = $this->pricemotion->getProduct($ean);
        } catch (\RuntimeException $e) {
            $this->logger->error(sprintf(
                "Could not get Pricemotion data for product %d with EAN %s: %s",
                $product->getId(), $ean->toString(), $e->getMessage()
            ));
            return;
        }

        $update = [
            Constants::ATTR_LOWEST_PRICE => $pricemotion_product->getLowestPrice(),
            Constants::ATTR_UPDATED_AT => microtime(true),
        ];

        if (($new_price = $this->getNewPrice($product, $pricemotion_product)) !== null) {
            $update[$this->priceAttribute] = $new_price;
        }

        foreach ($update as $attr => $value) {
            $product->setData($attr, $value);
        }

        $this->productSaveObserver->setLowestPriceRatio($product);

        if ($product->hasData(Constants::ATTR_LOWEST_PRICE_RATIO)) {
            $update[Constants::ATTR_LOWEST_PRICE_RATIO] = $product->getData(Constants::ATTR_LOWEST_PRICE_RATIO);
        }

        $this->productAction->updateAttributes([$product->getId()], $update, $product->getStoreId());
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
        } catch (\InvalidArgumentException $e) {
            $this->logger->error(sprintf(
                "Invalid price rule for product %d: %s",
                $product->getId(), $e->getMessage()
            ));
            return null;
        }

        $new_price = $rule->calculate($pricemotion_product);
        if (!$new_price) {
            return null;
        }

        if (!$this->priceAttribute) {
            $this->logger->error(sprintf(
                "Prices rules are configured for product %d, but no price attribute is configured",
                $product->getId()
            ));
            return null;
        }

        if (!empty($settings['protectMargin'])) {
            $minimum_margin = (float) $settings['minimumMargin'];
            $cost = (float) $product->getData(CostInterface::COST);
            if ($cost < 0.01) {
                $this->logger->error(sprintf(
                    "Margin protection enabled, but no cost price found for product %d",
                    $product->getId()
                ));
                return null;
            }
            $minimum_price = round($cost * (1 + $minimum_margin / 100), 2);
            if ($new_price < $minimum_price) {
                $this->logger->info(sprintf(
                    "Using minimum margin protection price %.4f instead of %.4f for product %d (%.4f + %.4f%%)",
                    $minimum_price, $new_price, $product->getId(), $cost, $minimum_margin
                ));
                $new_price = $minimum_price;
            }
        }

        if (!empty($settings['limitListPriceDiscount'])) {
            if (!$this->listPriceAttribute) {
                $this->logger->warning(sprintf(
                    "Maximum list price discount set for product %d, but no list price attribute is configured",
                    $product->getId()
                ));
            } elseif (($list_price = (float) $product->getData($this->listPriceAttribute)) < 0.01) {
                $this->logger->warning(sprintf(
                    "Maximum list price discount enabled, but no list price found for product %d",
                    $product->getId()
                ));
            } else {
                $maximum_discount = (float) $settings['maximumListPriceDiscount'];
                $minimum_price = round($list_price * (1 - $maximum_discount / 100), 2);
                if ($new_price < $minimum_price) {
                    $this->logger->info(sprintf(
                        "Using maximum list price discount price %.4f instead of %.4f for product %d (%.4f - %.4f%%)",
                        $minimum_price, $new_price, $product->getId(), $list_price, $maximum_discount
                    ));
                    $new_price = $minimum_price;
                }
            }
        }

        if (abs($product->getData($this->priceAttribute) - $new_price) < 0.005) {
            $this->logger->debug(sprintf(
                "Would adjust product %d price to %.2f according to %s, but it is already there",
                $product->getId(),
                $new_price,
                get_class($rule)
            ));
            return null;
        }

        $this->logger->info(sprintf(
            "Adjusting product %d price from %.2f to %.2f according to %s",
            $product->getId(),
            (float) $product->getData($this->priceAttribute),
            $new_price,
            get_class($rule)
        ));

        return $new_price;
    }
}