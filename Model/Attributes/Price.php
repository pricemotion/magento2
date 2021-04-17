<?php
namespace Pricemotion\Magento2\Model\Attributes;

use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\Model\ConfigurableAttribute;
use Pricemotion\Magento2\Model\FloatAttribute;

class Price extends ConfigurableAttribute {
    use FloatAttribute;

    public function getCodeFromConfig(Config $config): ?string {
        return $config->getPriceAttribute();
    }
}
