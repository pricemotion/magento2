<?php
namespace Pricemotion\Magento2\App;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config {
    private $config;

    public function __construct(ScopeConfigInterface $config) {
        $this->config = $config;
    }

    public function requireEanAttribute(): string {
        $attribute = $this->getEanAttribute();
        if ($attribute === null) {
            throw new ConfigurationException('EAN attribute must be configured');
        }
        return $attribute;
    }

    public function getEanAttribute(): ?string {
        return $this->config->getValue('pricemotion/attributes/ean') ?: null;
    }

    public function getPriceAttribute(): ?string {
        return $this->config->getValue('pricemotion/attributes/price') ?: null;
    }

    public function getListPriceAttribute(): ?string {
        return $this->config->getValue('pricemotion/attributes/list_price') ?: null;
    }

    public function getApiToken(): ?string {
        $apiKey = $this->getApiKey();

        if ($apiKey === null) {
            return null;
        }

        $expiresAt = time() + 3600;

        return $this->base64encode(implode('', [
            hash('sha256', $apiKey, true),
            hash_hmac('sha256', (string) $expiresAt, $apiKey, true),
            pack('P', $expiresAt),
        ]));
    }

    public function getApiKey(): ?string {
        return $this->config->getValue('pricemotion/general/api_key');
    }

    private function base64encode(string $data): string {
        $result = base64_encode($data);
        $result = rtrim($result, '=');
        return strtr($result, [
            '+' => '-',
            '/' => '_',
        ]);
    }
}
