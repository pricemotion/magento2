<?php
namespace Pricemotion\Magento2\App\PriceRule;

use Pricemotion\Magento2\App\Product;

class Disabled implements PriceRuleInterface {
    public function calculate(Product $product): ?float {
        return null;
    }
}
