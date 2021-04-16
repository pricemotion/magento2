<?php
namespace Pricemotion\Magento2\App\Push\Action;

use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\App\Push\Action;

class Status implements Action {
    public function execute(array $request) {
        return ['version' => Constants::getVersion()];
    }
}
