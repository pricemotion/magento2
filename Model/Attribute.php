<?php
namespace Pricemotion\Magento2\Model;

use Magento\Catalog\Model\Product;

abstract class Attribute {
    public function get(Product $product) {
        $attribute = $this->getCode();
        if (!$attribute) {
            return null;
        }
        $value = $product->getData($attribute);
        if (!is_string($value) || trim($value) == '') {
            return null;
        }
        return $this->decode($value);
    }

    abstract public function getCode(): ?string;

    abstract public function decode(string $value);
}
