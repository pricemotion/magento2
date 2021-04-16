<?php
namespace Pricemotion\Magento2\App\Push;

class InternalException extends Exception {
    public function getHttpResponseCode(): int {
        return 500;
    }
}
