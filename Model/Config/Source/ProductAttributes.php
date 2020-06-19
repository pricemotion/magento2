<?php
namespace Pricemotion\Magento2\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as ProductAttributeCollection;
use Magento\Framework\Data\OptionSourceInterface;

class ProductAttributes implements OptionSourceInterface {
    private $attributeCollection;

    public function __construct(ProductAttributeCollection $attributeCollection) {
        $this->attributeCollection = $attributeCollection;
    }

    public function toOptionArray(): array {
        $result = [
            [
                'value' => '',
                'label' => '',
            ],
        ];

        /** @var Attribute $attribute */
        foreach ($this->attributeCollection as $attribute) {
            $result[] = [
                'value' => $attribute->getName(),
                'label' => $attribute->getStoreLabel(),
            ];
        }

        return $result;
    }
}