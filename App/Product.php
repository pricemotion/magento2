<?php
namespace Pricemotion\Magento2\App;

class Product {

    private $lowestPrice;

    private function __construct() {}

    public static function fromXmlResponse(\DOMDocument $document): self {
        $root = $document->documentElement;

        if (!strcasecmp($root->tagName, 'error')) {
            throw new \RuntimeException("API error: " . trim($root->textContent));
        }

        if (strcasecmp($root->tagName, 'response')) {
            throw new \RuntimeException("Response root element should be <response>, not <{$root->tagName}>");
        }

        $product = new self;
        $product->lowestPrice = self::getFloat($root, 'info/price/min');

        return $product;
    }

    private static function getFloat(\DOMElement $root, string $query): float {
        $elements = (new \DOMXPath($root->ownerDocument))->query($query, $root);
        if ($elements->length !== 1) {
            throw new \RuntimeException("Expected exactly one result from query '{$query}'");
        }
        return (float) trim($elements->item(0)->textContent);
    }

    public function getLowestPrice(): float {
        return $this->lowestPrice;
    }

}