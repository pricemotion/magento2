<?php
namespace Pricemotion\Magento2\Cron;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\PricemotionClient;
use Psr\Log\LoggerInterface;

class Subscribe {
    private $logger;
    private $productCollectionFactory;
    private $config;
    private $pricemotion_client;

    public function __construct(
        LoggerInterface $logger,
        CollectionFactory $product_collection_factory,
        Config $config,
        PricemotionClient $pricemotion_client
    ) {
        $this->logger = $logger;
        $this->productCollectionFactory = $product_collection_factory;
        $this->config = $config;
        $this->pricemotion_client = $pricemotion_client;
    }

    public function execute(): void {
        // TODO: Subscribe to each unsubscribed product
        // TODO: Once per 24 hours, subscribe to all products

        $ean_attribute = $this->config->getEanAttribute();
        if (!$ean_attribute) {
            $this->logger->warning(sprintf(
                "%s: No EAN product attribute is configured; not updating products",
                __CLASS__
            ));
            return;
        }

        $eans = $this->getAllEans($ean_attribute);

        try {
            $this->pricemotion_client->subscribe($eans);
            $this->logger->info(sprintf(
                "%s: Successfully subscribed to %d EANs",
                __CLASS__, sizeof($eans)
            ));
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                "%s: Caught %s while trying to subscribe to %d EANs: (%d) %s",
                __CLASS__, get_class($e), sizeof($eans), $e->getCode(), $e->getMessage()
            ));
        }
    }

    private function getAllEans(string $ean_attribute): array {
        $result = [];

        $product_collection = $this->productCollectionFactory->create();
        $product_collection->addAttributeToSelect($ean_attribute);

        foreach ($product_collection->getAllAttributeValues($ean_attribute) as $store_eans) {
            foreach ($store_eans as $ean) {
                $ean = trim($ean);
                $ean = ltrim($ean, '0');
                if ($ean == '' || !ctype_digit($ean)) {
                    continue;
                }
                $result[$ean] = true;
            }
        }

        return array_keys($result);
    }
}