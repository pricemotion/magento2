<?php
namespace Pricemotion\Magento2\Model\Attributes;

use InvalidArgumentException;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\Ean as EanValue;
use Pricemotion\Magento2\Model\ConfigurableAttribute;

class Ean extends ConfigurableAttribute {
    public function getCodeFromConfig(Config $config): ?string {
        return $config->getEanAttribute();
    }

    public function decode(string $value): ?EanValue {
        try {
            return EanValue::fromString($value);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }
}
