<?php

namespace Pricemotion\Magento2\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\Backend\JsonEncoded;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Pricemotion\Magento2\App\Constants;

/** @phan-suppress-next-line PhanUnreferencedClass */
class AttributesPatch implements DataPatchInterface, PatchRevertableInterface {
    private $eavSetup;

    public function __construct(EavSetup $eavSetup) {
        $this->eavSetup = $eavSetup;
    }

    public static function getDependencies(): array {
        return [];
    }

    public function getAliases(): array {
        return [];
    }

    public function apply(): self {
        $this->addProductAttribute(
            Constants::ATTR_LOWEST_PRICE,
            [
                'label' => 'Lowest Price',
                'type' => 'decimal',
                'input' => 'price',
                'required' => false,
                'is_used_in_grid' => true,
                'is_filterable_in_grid' => true,
                'visible' => false,
            ]
        );

        $this->addProductAttribute(
            Constants::ATTR_LOWEST_PRICE_RATIO,
            [
                'label' => 'Price Difference (%)',
                'type' => 'decimal',
                'input' => 'text',
                'required' => false,
                'is_used_in_grid' => true,
                'is_filterable_in_grid' => true,
                'visible' => false,
            ]
        );

        $this->addProductAttribute(
            Constants::ATTR_UPDATED_AT,
            [
                'label' => 'Pricemotion Timestamp',
                'type' => 'decimal',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'is_filterable' => true,
            ]
        );

        $this->addProductAttribute(
            Constants::ATTR_SETTINGS,
            [
                'label' => 'Pricemotion Settings',
                'type' => 'text',
                'input' => 'text',
                'required' => false,
                'visible' => false,
                'backend' => JsonEncoded::class,
            ]
        );

        return $this;
    }

    private function addProductAttribute(string $attrName, array $options): void {
        if ($this->eavSetup->getAttribute(Product::ENTITY, $attrName)) {
            return;
        }
        $this->eavSetup->addAttribute(Product::ENTITY, $attrName, $options);
    }

    public function revert(): void {
        $this->eavSetup->removeAttribute(Product::ENTITY, Constants::ATTR_LOWEST_PRICE);
        $this->eavSetup->removeAttribute(Product::ENTITY, Constants::ATTR_LOWEST_PRICE_RATIO);
        $this->eavSetup->removeAttribute(Product::ENTITY, Constants::ATTR_UPDATED_AT);
        $this->eavSetup->removeAttribute(Product::ENTITY, Constants::ATTR_SETTINGS);
    }
}