<?php
namespace Pricemotion\Magento2\Model\Attributes;

use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\Model\Attribute;
use Pricemotion\Magento2\Model\FloatAttribute;

class LowestPrice extends Attribute {
    use FloatAttribute;

    public function getCode(): ?string {
        return Constants::ATTR_LOWEST_PRICE;
    }
}
