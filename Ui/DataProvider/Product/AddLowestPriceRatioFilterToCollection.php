<?php
namespace Pricemotion\Magento2\Ui\DataProvider\Product;

use Magento\Framework\Data\Collection;
use Magento\Ui\DataProvider\AddFilterToCollectionInterface;

class AddLowestPriceRatioFilterToCollection implements AddFilterToCollectionInterface {

    public function addFilter(Collection $collection, $field, $condition = null) {
        $collection->addFieldToFilter($field, array_map(function ($value) {
            return 1 + $value / 100;
        }, $condition));
    }

}