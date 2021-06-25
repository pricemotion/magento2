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
        $product = $this->getProductFromXml('
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
        ');
        $this->assertEquals(90.09, $rule->calculate($product));
    }

    public function testLessThanPosition() {
        $rule = (new Factory())->fromArray([
            'rule' => 'lessThanPosition',
            'lessThanPosition' => 2,
        ]);
        $this->assertInstanceOf(LessThanPosition::class, $rule);
        $product = $this->getProductFromXml('
            <response>
                <info>
                    <price>
                        <min>0</min>
                        <max>0</max>
                        <avg>100.10</avg>
                    </price>
                </info>
                <prices>
                    <bezorg>
                        <item>
                            <seller>A</seller>
                            <price>10.00</price>
                        </item>
                        <item>
                            <seller>B</seller>
                            <price>20.00</price>
                        </item>
                        <item>
                            <seller>C</seller>
                            <price>30.00</price>
                        </item>
                    </bezorg>
                </prices>
            </response>
        ');
        $this->assertEquals(19.99, $rule->calculate($product));
    }

    private function getProductFromXml($xml) {
        $document = new DOMDocument();
        $document->loadXML("<?xml version='1.0'?>{$xml}");
        return Product::fromXmlResponse($document);
    }
}
