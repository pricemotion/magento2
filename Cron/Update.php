<?php
namespace Pricemotion\Magento2\Cron;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\EAN;
use Pricemotion\Magento2\App\PricemotionClient;
use Psr\Log\LoggerInterface;

class Update {
    private $logger;
    private $productCollectionFactory;
    private $config;
    private $pricemotion;
    private $eanAttribute;

    public function __construct(
        LoggerInterface $logger,
        CollectionFactory $product_collection_factory,
        Config $config,
        PricemotionClient $pricemotion_client
    ) {
        $this->logger = $logger;
        $this->productCollectionFactory = $product_collection_factory;
        $this->config = $config;
        $this->pricemotion = $pricemotion_client;
    }

    public function execute(): void {
        // TODO: Subscribe to each unsubscribed product
        // TODO: Once per 24 hours, subscribe to all products

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

        $product = $this->pricemotion->getProduct($ean);
    }
}