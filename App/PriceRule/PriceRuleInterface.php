<?php
namespace Pricemotion\Magento2\App\PriceRule;

use Pricemotion\Magento2\App\Product;

interface PriceRuleInterface {
    public function calculate(Product $product): ?float;
}
