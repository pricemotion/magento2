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

    public function getApiToken(): ?string {
        if (!$this->config->getApiKey()) {
            return null;
        }

        $expiresAt = time() + 3600;

        return $this->base64encode(implode('', [
            hash('sha256', $this->config->getApiKey(), true),
            hash_hmac('sha256', $expiresAt, $this->config->getApiKey(), true),
            pack('P', $expiresAt),
        ]));
    }

    private function base64encode(string $data): string {
        $result = base64_encode($data);
        $result = rtrim($result, '=');
        $result = strtr($result, [
            '+' => '-',
            '/' => '_',
        ]);
        return $result;
    }
}