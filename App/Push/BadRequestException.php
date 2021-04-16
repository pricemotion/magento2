<?php
namespace Pricemotion\Magento2\App\Push;

class BadRequestException extends Exception {
    public function getHttpResponseCode(): int {
        return 400;
    }
}
