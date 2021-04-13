<?php
namespace Pricemotion\Magento2\App;

use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase {
    public function testFromXmlResponse() {
        $product = self::getProduct();

        $this->assertEquals(52.50, $product->getLowestPrice());
        $this->assertEquals(60.67, $product->getAveragePrice());

        $this->assertCount(5, $product->getOffers());
        $lastOffer = null;
        /** @var Offer $offer */
        foreach ($product->getOffers() as $offer) {
            if ($lastOffer) {
                $this->assertGreaterThanOrEqual($lastOffer->getPrice(), $offer->getPrice());
            }
            $lastOffer = $offer;
        }
    }

    public static function getProduct(): Product {
        $document = new \DOMDocument();
        $document->load(__DIR__ . '/../resources/product.xml');
        return Product::fromXmlResponse($document);
    }
}
