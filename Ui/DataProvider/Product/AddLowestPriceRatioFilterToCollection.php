<?php
namespace Pricemotion\Magento2\Ui\DataProvider\Product;

use Magento\Framework\Data\Collection;
use Magento\Ui\DataProvider\AddFilterToCollectionInterface;

/** @phan-suppress-next-line PhanUnreferencedClass */
class AddLowestPriceRatioFilterToCollection implements AddFilterToCollectionInterface {
    public function addFilter(Collection $collection, $field, $condition = null) {
        if (!is_array($condition)) {
            return;
        }
        $collection->addFieldToFilter(
            $field,
            array_map(function ($value) {
                return 1 + (float) $value / 100;
            }, $condition),
        );
    }
}
