<?php
namespace Pricemotion\Magento2\Model\Attributes;

use InvalidArgumentException;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\Model\ConfigurableAttribute;
use Pricemotion\Sdk;

class Ean extends ConfigurableAttribute {
    public function getCodeFromConfig(Config $config): ?string {
        return $config->getEanAttribute();
    }

    public function decode(string $value): ?Sdk\Data\Ean {
        try {
            return Sdk\Data\Ean::fromString($value);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }
}
