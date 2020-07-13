<?php
namespace Pricemotion\Magento2\App\PriceRule;

use Pricemotion\Magento2\App\Product;
use PHPUnit\Framework\TestCase;
use Pricemotion\Magento2\App\ProductTest;

class EqualToPositionTest extends TestCase {
    public function testCalculate() {
        $product = ProductTest::getProduct();
        $this->assertEquals(52.50, (new EqualToPosition(1))->calculate($product));
        $this->assertEquals(60.06, (new EqualToPosition(3))->calculate($product));
        $this->assertEquals(69.51, (new EqualToPosition(10))->calculate($product));
    }
}