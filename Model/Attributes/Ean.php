<?php
namespace Pricemotion\Magento2\Model\Attributes;

use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\Model\ConfigurableAttribute;

class Ean extends ConfigurableAttribute {
    public function getCodeFromConfig(Config $config): ?string {
        return $config->getEanAttribute();
    }

    public function decode(string $value): ?string {
        return $value;
    }
}
