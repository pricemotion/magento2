<?php
namespace Pricemotion\Magento2\Model;

use Magento\Catalog\Api\Data\CostInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\Logger\Logger;

class ProductRepository {
    private $collectionFactory;

    private $config;

    private $logger;

    public function __construct(CollectionFactory $collectionFactory, Config $config, Logger $logger) {
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function getAll(): array {
        return $this->getFiltered(function (): void {});
    }

    public function getByEans(array $eans): array {
        return $this->getFiltered(function (Collection $collection) use ($eans): void {
            $collection->addAttributeToFilter(
                $this->config->requireEanAttribute(),
                array_map(function ($ean) {
                    return ['eq' => $ean];
                }, $eans),
            );
        });
    }

    public function getForUpdate(int $updateInterval): array {
        return $this->getFiltered(function (Collection $collection) use ($updateInterval): void {
            $collection->addAttributeToFilter(Constants::ATTR_UPDATED_AT, [
                ['null' => true],
                ['lt' => microtime(true) - $updateInterval],
            ]);
        });
    }

    /** @return Product[] */
    private function getFiltered(\Closure $filter): array {
        $collection = $this->collectionFactory->create();
        if (!$collection instanceof Collection) {
            throw new \LogicException('CollectionFactory::create() is expected to return an instance of Collection');
        }

        $collection->addAttributeToSelect(Constants::ATTR_UPDATED_AT, 'left');
        $collection->addAttributeToSelect($this->config->requireEanAttribute());
        $this->addOptionalAttributeToSelect($collection, $this->config->getPriceAttribute());
        $this->addOptionalAttributeToSelect($collection, $this->config->getListPriceAttribute());
        $collection->addAttributeToSelect(Constants::ATTR_SETTINGS, 'left');
        $collection->addAttributeToSelect(CostInterface::COST, 'left');
        $collection->addAttributeToSelect(Constants::ATTR_LOWEST_PRICE, 'left');
        $collection->addAttributeToSelect(Constants::ATTR_LOWEST_PRICE_RATIO, 'left');

        $collection->addAttributeToFilter($this->config->requireEanAttribute(), ['neq' => '']);

        $collection->addPriceData();

        $filter($collection);

        $startTime = microtime(true);

        $result = $collection->getItems();

        $result = array_map(function ($item) {
            if (!$item instanceof Product) {
                throw new \LogicException('Collection::getItems() is expected to return an array of Product instances');
            }
            return $item;
        }, $result);

        $this->logger->info(sprintf('Retrieved %d products in %.2f s', sizeof($result), microtime(true) - $startTime));

        return $result;
    }

    private function addOptionalAttributeToSelect(Collection $collection, ?string $attribute): void {
        if ($attribute !== null) {
            $collection->addAttributeToSelect($attribute, 'left');
        }
    }
}
