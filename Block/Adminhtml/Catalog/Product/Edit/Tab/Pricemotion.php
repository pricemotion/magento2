<?php
namespace Pricemotion\Magento2\Block\Adminhtml\Catalog\Product\Edit\Tab;

use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\Block\Adminhtml\Catalog\Product\Widget;

class Pricemotion extends Widget {
    protected function getWidgetPath(): string {
        return '/widget';
    }

    protected function getWidgetParameters(): \stdClass {
        $eanAttribute = $this->config->getEanAttribute();

        return (object) [
            'token' => $this->config->getApiToken(),
            'ean' => $eanAttribute ? $this->getProduct()->getData($eanAttribute) : null,
            'settings' => $this->getProduct()->getData(Constants::ATTR_SETTINGS) ?: new \stdClass(),
        ];
    }
}
