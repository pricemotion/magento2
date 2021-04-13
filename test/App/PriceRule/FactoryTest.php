<?php
namespace Pricemotion\Magento2\App\PriceRule;

use DOMDocument;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pricemotion\Magento2\App\Product;

class FactoryTest extends TestCase {
    public function testDisabled() {
        $factory = new Factory();
        $rule = $factory->fromArray(['rule' => 'disabled']);
        $this->assertInstanceOf(Disabled::class, $rule);
    }

    public function testMissingParameter() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Required parameter missing for rule: percentageBelowAverage');
        (new Factory())->fromArray(['rule' => 'percentageBelowAverage']);
    }

    public function testPercentageBelowAverage() {
        $rule = (new Factory())->fromArray([
            'rule' => 'percentageBelowAverage',
            'percentageBelowAverage' => 10,
        ]);
        $this->assertInstanceOf(PercentageBelowAverage::class, $rule);
        $document = new DOMDocument();
        $document->loadXML("<?xml version='1.0'?>
            <response>
                <info>
                    <price>
                        <min>0</min>
                        <max>0</max>
                        <avg>100.10</avg>
                    </price>
                </info>
                <prices></prices>
            </response>
        ");
        $product = Product::fromXmlResponse($document);
        $this->assertEquals(90.09, $rule->calculate($product));
    }
}
