<?php
namespace Pricemotion\Magento2\App;

class Product {

    private $lowestPrice;

    private function __construct() {}

    public static function fromXmlResponse(\DOMDocument $document): self {
    }

}