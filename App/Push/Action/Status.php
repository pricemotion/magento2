<?php
namespace Pricemotion\Magento2\App\Push\Action;

use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\App\Push\Action;

class Status implements Action {
    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    public function execute(array $request) {
        return [
            'version' => Constants::getVersion(),
            'eanAttribute' => $this->config->getEanAttribute(),
            'priceAttribute' => $this->config->getPriceAttribute(),
            'listPriceAttribute' => $this->config->getListPriceAttribute(),
        ];
    }
}
