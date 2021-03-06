<?php
namespace Pricemotion\Magento2\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\Backend\JsonEncoded;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Pricemotion\Magento2\App\Constants;

/** @phan-suppress-next-line PhanUnreferencedClass */
class InstallData implements InstallDataInterface {
    private $eavSetup;

    public function __construct(EavSetup $eav_setup) {
        $this->eavSetup = $eav_setup;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
        $this->eavSetup->addAttribute(
            Product::ENTITY,
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

        $this->eavSetup->addAttribute(
            Product::ENTITY,
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

        $this->eavSetup->addAttribute(
            Product::ENTITY,
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

        $this->eavSetup->addAttribute(
            Product::ENTITY,
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
    }
}
