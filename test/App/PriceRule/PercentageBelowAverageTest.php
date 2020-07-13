<?php
namespace Pricemotion\Magento2\App\PriceRule;

use PHPUnit\Framework\TestCase;
use Pricemotion\Magento2\App\ProductTest;

class PercentageBelowAverageTest extends TestCase {
    public function testCalculate() {
        $product = ProductTest::getProduct();
        $this->assertEquals(45.50, (new PercentageBelowAverage(25))->calculate($product));
    }
}