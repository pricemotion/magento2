<?php
namespace Pricemotion\Magento2\Model;

trait FloatAttribute {
    public function decode(string $value): ?float {
        if (!is_numeric($value)) {
            return null;
        }
        return (float) $value;
    }
}
