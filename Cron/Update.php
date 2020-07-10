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

class Update {
    private const UPDATE_INTERVAL = 3600 * 12;

    private $globalLogger;
    private $logger;
    private $productCollectionFactory;
    private $config;
    private $pricemotion;
    private $eanAttribute;
    private $productResourceModel;

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

    public function execute(): void {
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
        $product_collection->addAttributeToSelect($this->eanAttribute);
        $product_collection->addAttributeToSelect(Constants::ATTR_UPDATED_AT);
        $product_collection->addPriceData();
        $product_collection->walk(function (Product $product) {
            $this->updateProduct($product);
        });
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

        $now = microtime(true);
        if ((float) $product->getData(Constants::ATTR_UPDATED_AT) > $now - self::UPDATE_INTERVAL) {
            $this->logger->debug(sprintf(
                "Skipping product %d because it was updated %.3f s ago",
                $product->getId(),
                $now - $product->getData(Constants::ATTR_UPDATED_AT)
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

        $product->setData(Constants::ATTR_LOWEST_PRICE, $pricemotion_product->getLowestPrice());

        if ($price = (float) $product->getPrice()) {
            $product->setData(Constants::ATTR_LOWEST_PRICE_RATIO, $pricemotion_product->getLowestPrice() / $price);
        } else {
            $product->unsetData(Constants::ATTR_LOWEST_PRICE_RATIO);
        }

        $product->setData(Constants::ATTR_UPDATED_AT, microtime(true));

        $this->productResourceModel->save($product);
    }
}