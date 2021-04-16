<?php
namespace Pricemotion\Magento2\App\Push;

interface Action {
    public function execute(array $request);
}
