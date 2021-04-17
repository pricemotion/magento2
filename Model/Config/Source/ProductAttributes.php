<?php
namespace Pricemotion\Magento2\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as ProductAttributeCollection;
use Magento\Framework\Data\OptionSourceInterface;

/** @phan-suppress-next-line PhanUnreferencedClass */
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

        foreach ($this->attributeCollection as $attribute) {
            if (!$attribute instanceof Attribute) {
                continue;
            }
            $result[] = [
                'value' => $attribute->getName(),
                'label' => $attribute->getStoreLabel(),
            ];
        }

        return $result;
    }
}
