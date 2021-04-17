<?php
namespace Pricemotion\Magento2\Model\Attributes;

use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\Model\Attribute;

class Settings extends Attribute {
    public function getCode(): ?string {
        return Constants::ATTR_SETTINGS;
    }

    public function decode(string $value): ?array {
        $value = json_decode($value, true);
        if (!is_array($value)) {
            return null;
        }
        return $value;
    }
}
