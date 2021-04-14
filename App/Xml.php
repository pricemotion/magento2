<?php
namespace Pricemotion\Magento2\App;

class Xml {
    public static function getFloat(\DOMNode $root, string $query): float {
        return (float) self::getText($root, $query);
    }

    public static function getText(\DOMNode $root, string $query): string {
        return trim(self::get($root, $query)->textContent);
    }

    public static function get(\DOMNode $root, string $query): \DOMNode {
        $elements = self::getAll($root, $query);
        if ($elements->length !== 1) {
            throw new \RuntimeException("Expected exactly one result from query '{$query}'");
        }
        return $elements->item(0);
    }

    public static function getAll(\DOMNode $root, string $query): \DOMNodeList {
        return (new \DOMXPath($root->ownerDocument))->query($query, $root);
    }
}
