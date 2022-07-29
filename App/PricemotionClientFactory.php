<?php
namespace Pricemotion\Magento2\App;

use Pricemotion\Sdk;

class PricemotionClientFactory {
    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    public function make(): Sdk\Api\Client {
        $token = $this->config->getApiToken();

        if ($token === null) {
            throw new \RuntimeException('Pricemotion API key is not configured');
        }

        return new Sdk\Api\Client($token);
    }
}
