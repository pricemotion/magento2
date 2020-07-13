<?php
namespace Pricemotion\Magento2\App;

class Offer {
    private $seller;
    private $price;

    private function __construct() {}

    public static function fromElement(\DOMElement $item): self {
        $offer = new self;
        $offer->seller = Xml::getText($item, 'seller');
        $offer->price = Xml::getFloat($item, 'price');
        return $offer;
    }

    public function getSeller(): string {
        return $this->seller;
    }

    public function getPrice(): float {
        return $this->price;
    }
}