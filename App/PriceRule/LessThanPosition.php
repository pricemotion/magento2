<?php
namespace Pricemotion\Magento2\App\PriceRule;

use Pricemotion\Magento2\App\Product;

class LessThanPosition implements PriceRuleInterface {
    private $value;

    public function __construct($value) {
        $this->value = (int) $value;
        if ($this->value < 1) {
            throw new \InvalidArgumentException('Less than position value must be at least 1');
        }
    }

    public function calculate(Product $product): ?float {
        $position = 0;
        foreach ($product->getOffers() as $i => $offer) {
            if (++$position >= $this->value) {
                return $offer->getPrice() - 0.01;
            }
        }
        if (isset($offer)) {
            return $offer->getPrice() + 0.01;
        }
        return null;
    }
}
