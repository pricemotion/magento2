<?php
namespace Pricemotion\Magento2\App\PriceRule;

use Pricemotion\Magento2\App\Product;

class PercentageBelowAverage implements PriceRuleInterface {
    private $value;

    public function __construct($value) {
        $this->value = ((float) $value) / 100;
        if ($this->value < 0 || $this->value > 1) {
            throw new \InvalidArgumentException('Percentage below average value must be between 0% and 100%');
        }
    }

    public function calculate(Product $product): ?float {
        return $this->roundDown($product->getAveragePrice() * (1 - $this->value), 0.01);
    }

    private function roundDown(float $value, float $step) {
        return floor($value / $step) * $step;
    }
}
