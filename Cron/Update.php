<?php
namespace Pricemotion\Magento2\Cron;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Logger\Monolog;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\App\EAN;
use Pricemotion\Magento2\App\PricemotionClient;
use Pricemotion\Magento2\App\PriceRule;
use Psr\Log\InvalidArgumentException;

class Update {
    private const UPDATE_INTERVAL = 3600 * 12;
    private const MAX_DURATION = 55;

    private $globalLogger;
    private $logger;
    private $productCollectionFactory;
    private $config;
    private $pricemotion;
    private $eanAttribute;
    private $productResourceModel;
    private $ignoreUpdatedAt = false;

    public function __construct(
        Monolog $logger,
        CollectionFactory $product_collection_factory,
        Config $config,
        PricemotionClient $pricemotion_client,
        ProductResourceModel $product_resource_model
    ) {
        $this->globalLogger = $logger;
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

        $this->logger = $this->globalLogger->withName('pricemotion');

        $this->eanAttribute = $this->config->getEanAttribute();
        if (!$this->eanAttribute) {
            $this->logger->warning(sprintf(
                "%s: No EAN product attribute is configured; not updating products",
                __CLASS__
            ));
            return;
        }

        $product_collection = $this->productCollectionFactory->create();

        $product_collection->addAttributeToFilter($this->eanAttribute, ['neq' => '']);

        if (!$this->ignoreUpdatedAt) {
            $product_collection->addAttributeToFilter(Constants::ATTR_UPDATED_AT, [
                ['null' => true],
                ['lt' => microtime(true) - self::UPDATE_INTERVAL],
            ]);
        }

        $product_collection->addAttributeToSelect($this->eanAttribute);
        $product_collection->addAttributeToSelect(Constants::ATTR_UPDATED_AT);
        $product_collection->addAttributeToSelect(Constants::ATTR_SETTINGS);

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

        $settings = $product->getData(Constants::ATTR_SETTINGS);

        $rule = new PriceRule\Disabled();
        if ($settings) {
            try {
                $rule = (new PriceRule\Factory())->fromJson((string) $settings);
            } catch (InvalidArgumentException $e) {
                $this->logger->error(sprintf(
                    "Invalid price rule for product %d: %s",
                    $product->getId(), $e->getMessage()
                ));
            }
        }

        $new_price = $rule->calculate($pricemotion_product);
        if ($new_price && abs($product->getData(Product::PRICE) - $new_price) > 0.005) {
            $this->logger->info(sprintf(
                "Adjusting product %d price from %.2f to %.2f according to %s",
                $product->getId(),
                (float) $product->getData(Product::PRICE),
                $new_price,
                get_class($rule)
            ));
            $product->setData(Product::PRICE, $new_price);
        }

        $product->setData(Constants::ATTR_LOWEST_PRICE, $pricemotion_product->getLowestPrice());

        if ($price = (float) $product->getPrice()) {
            $product->setData(Constants::ATTR_LOWEST_PRICE_RATIO, $price / $pricemotion_product->getLowestPrice());
        } else {
            $product->unsetData(Constants::ATTR_LOWEST_PRICE_RATIO);
        }

        $product->setData(Constants::ATTR_UPDATED_AT, microtime(true));

        $this->productResourceModel->save($product);
    }
}