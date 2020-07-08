<?php
namespace Pricemotion\Magento2\App;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config {
    private $config;

    public function __construct(ScopeConfigInterface $config) {
        $this->config = $config;
    }

    public function getEanAttribute(): ?string {
        return $this->config->getValue('pricemotion/attributes/ean');
    }

    public function getApiKey(): ?string {
        return $this->config->getValue('pricemotion/general/api_key');
    }
}