<?php
namespace Pricemotion\Magento2\Cron;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\App\EAN;
use Pricemotion\Magento2\App\PricemotionClient;
use Psr\Log\LoggerInterface;

class Update {
    private $logger;
    private $productCollectionFactory;
    private $config;
    private $pricemotion;
    private $eanAttribute;
    private $productResourceModel;

    public function __construct(
        LoggerInterface $logger,
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

    public function execute(): void {
        // TODO: Update each product only once per 24 hours

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
                "Skip invalid EAN '%s' on product %d: %s",
                $ean_string, $product->getId(), $e->getMessage()
            ));
        }

        try {
            $pricemotion_product = $this->pricemotion->getProduct($ean);
        } catch (\RuntimeException $e) {
            $this->logger->error(sprintf(
                "Could not get Pricemotion data for product %d with EAN %s: %s",
                $product->getId(), $ean->toString(), $e->getMessage()
            ));
        }

        $product->setData(Constants::ATTR_LOWEST_PRICE, $pricemotion_product->getLowestPrice());

        if ($price = (float) $product->getPrice()) {
            $product->setData(Constants::ATTR_LOWEST_PRICE_RATIO, $pricemotion_product->getLowestPrice() / $price);
        } else {
            $product->unsetData(Constants::ATTR_LOWEST_PRICE_RATIO);
        }

        $this->productResourceModel->save($product);
    }
}