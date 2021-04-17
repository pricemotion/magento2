<?php
namespace Pricemotion\Magento2\Model;

use Magento\Catalog\Api\Data\CostInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\Constants;

class ProductRepository {
    private $collectionFactory;

    private $config;

    public function __construct(
        CollectionFactory $collectionFactory,
        Config $config
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
    }

    public function getAll(): array {
        return $this->getFiltered(function (Collection $collection): void {
        });
    }

    public function getByEans(array $eans): array {
        return $this->getFiltered(function (Collection $collection) use ($eans): void {
            $collection->addAttributeToFilter(
                $this->config->requireEanAttribute(),
                array_map(
                    function ($ean) {
                        return ['eq' => $ean];
                    },
                    $eans
                )
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

        $collection->addAttributeToSelect(Constants::ATTR_UPDATED_AT, 'left');
        $collection->addAttributeToSelect($this->config->requireEanAttribute());
        $this->addOptionalAttributeToSelect($collection, $this->config->getPriceAttribute());
        $this->addOptionalAttributeToSelect($collection, $this->config->getListPriceAttribute());
        $collection->addAttributeToSelect(Constants::ATTR_SETTINGS, 'left');
        $collection->addAttributeToSelect(CostInterface::COST, 'left');

        $collection->addAttributeToFilter($this->config->requireEanAttribute(), ['neq' => '']);

        $collection->addPriceData();

        $filter($collection);

        return $collection->getItems();
    }

    private function addOptionalAttributeToSelect(
        Collection $collection,
        ?string $attribute
    ): void {
        if ($attribute !== null) {
            $collection->addAttributeToSelect($attribute, 'left');
        }
    }
}
