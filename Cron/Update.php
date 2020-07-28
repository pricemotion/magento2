<?php
namespace Pricemotion\Magento2\Cron;

use Magento\Catalog\Api\Data\CostInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\App\EAN;
use Pricemotion\Magento2\App\PricemotionClient;
use Pricemotion\Magento2\App\PriceRule;
use Pricemotion\Magento2\App\Product as PricemotionProduct;
use Pricemotion\Magento2\Logger\Logger;

class Update {
    private const UPDATE_INTERVAL = 3600 * 12;
    private const MAX_DURATION = 55;

    private $logger;
    private $productCollectionFactory;
    private $config;
    private $pricemotion;
    private $eanAttribute;
    private $priceAttribute;
    private $listPriceAttribute;
    private $productResourceModel;
    private $ignoreUpdatedAt = false;

    public function __construct(
        Logger $logger,
        CollectionFactory $product_collection_factory,
        Config $config,
        PricemotionClient $pricemotion_client,
        ProductResourceModel $product_resource_model
    ) {
        $this->logger = $logger;
        $this->productCollectionFactory = $product_collection_factory;
        $this->config = $config;
        $this->pricemotion = $pricemotion_client;
        $this->productResourceModel = $product_resource_model;
    }

    public function setIgnoreUpdatedAt(bool $value): void {
        $this->ignoreUpdatedAt = $value;
    }

    public function execute(): void {
        $run_until = time() + self::MAX_DURATION;

        $this->eanAttribute = $this->config->getEanAttribute();
        if (!$this->eanAttribute) {
            $this->logger->warning("No EAN product attribute is configured; not updating products");
            return;
        }

        $this->priceAttribute = $this->config->getPriceAttribute();
        $this->listPriceAttribute = $this->config->getListPriceAttribute();

        $product_collection = $this->productCollectionFactory->create();

        $product_collection->addAttributeToFilter($this->eanAttribute, ['neq' => '']);

        if (!$this->ignoreUpdatedAt) {
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

        $this->logger->info(sprintf("Got %d products for update", sizeof($products)));

        shuffle($products);

        $processed = 0;
        foreach ($products as $product) {
            $this->updateProduct($product);
            $processed++;

            if (time() > $run_until) {
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

        $this->adjustPrice($product, $pricemotion_product);

        $product->setData(Constants::ATTR_LOWEST_PRICE, $pricemotion_product->getLowestPrice());

        $product->setData(Constants::ATTR_UPDATED_AT, microtime(true));

        $this->productResourceModel->save($product);
    }

    private function adjustPrice(Product $product, PricemotionProduct $pricemotion_product): void {
        $settings = $product->getData(Constants::ATTR_SETTINGS);
        if (!$settings) {
            return;
        }

        $settings = json_decode($settings, true);
        if (!is_array($settings)) {
            return;
        }

        try {
            $rule = (new PriceRule\Factory())->fromArray($settings);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error(sprintf(
                "Invalid price rule for product %d: %s",
                $product->getId(), $e->getMessage()
            ));
            return;
        }

        $new_price = $rule->calculate($pricemotion_product);
        if (!$new_price) {
            return;
        }

        if (!$this->priceAttribute) {
            $this->logger->error(sprintf(
                "Prices rules are configured for product %d, but no price attribute is configured",
                $product->getId()
            ));
            return;
        }

        if (!empty($settings['protectMargin'])) {
            $minimum_margin = (float) $settings['minimumMargin'];
            $cost = (float) $product->getData(CostInterface::COST);
            if ($cost < 0.01) {
                $this->logger->error(sprintf(
                    "Margin protection enabled, but no cost price found for product %d",
                    $product->getId()
                ));
                return;
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
            return;
        }

        $this->logger->info(sprintf(
            "Adjusting product %d price from %.2f to %.2f according to %s",
            $product->getId(),
            (float) $product->getData($this->priceAttribute),
            $new_price,
            get_class($rule)
        ));

        $product->setData($this->priceAttribute, $new_price);
    }
}