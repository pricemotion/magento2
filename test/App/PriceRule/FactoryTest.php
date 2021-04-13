<?php
namespace Pricemotion\Magento2\App\PriceRule;

use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase {
    public function testDisabled() {
        $factory = new Factory;
        $rule = $factory->fromArray(['rule' => 'disabled']);
        $this->assertInstanceOf(Disabled::class, $rule);
    }
}
