<?php
namespace Pricemotion\Magento2\App\Push;

abstract class Exception extends \Exception {
    public function getResponse() {
        $previous = $this->getPrevious();

        return [
            'error' => $this->getMessage(),
            'code' => $this->getCode(),
            'previous' => $previous ? (string) $previous : null,
        ];
    }

    abstract public function getHttpResponseCode(): int;
}
