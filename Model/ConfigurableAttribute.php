<?php
namespace Pricemotion\Magento2\Model;

use Pricemotion\Magento2\App\Config;

abstract class ConfigurableAttribute extends Attribute {
    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    public function getCode(): ?string {
        return $this->getCodeFromConfig($this->config);
    }

    abstract protected function getCodeFromConfig(Config $config): ?string;
}
