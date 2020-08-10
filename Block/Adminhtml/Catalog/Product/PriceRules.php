<?php
namespace Pricemotion\Magento2\Block\Adminhtml\Catalog\Product;

class PriceRules extends Widget {

    protected function getWidgetPath(): string {
        return '/rulesWidget';
    }

    protected function getWidgetParameters(): \stdClass {
        return (object) [];
    }

}