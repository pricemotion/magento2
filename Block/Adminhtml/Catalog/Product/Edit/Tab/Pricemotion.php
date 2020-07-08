<?php
namespace Pricemotion\Magento2\Block\Adminhtml\Catalog\Product\Edit\Tab;

use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;
use Pricemotion\Magento2\App\Config;

class Pricemotion extends Template {
    protected $_template = 'product/edit/pricemotion.phtml';
    private $coreRegistry;
    private $config;

    public function __construct(
        Template\Context $context,
        Registry $coreRegistry,
        DynamicCollector $csp,
        Config $config,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->config = $config;
        $csp->add(new FetchPolicy('frame-src', false, [$this->getWebUrl()]));
        parent::__construct($context, $data);
    }

    protected function _beforeToHtml() {
        $this->assign('settings', [
            'web_url' => $this->getWebUrl(),
            'ean' => $this->getProduct()->getData($this->config->getEanAttribute()),
            'token' => $this->getApiToken(),
        ]);

        return parent::_beforeToHtml();
    }

    private function getApiToken(): ?string {
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

    private function getProduct(): Product {
        return $this->coreRegistry->registry('current_product');
    }

    public function getViewFileUrl($fileId, array $params = []) {
        $url = parent::getViewFileUrl($fileId, $params);
        $url .= '?=' . uniqid(); // TODO: Only during development, otherwise append version
        return $url;
    }

    private function getWebUrl() {
        return 'http://localhost:8080'; // TODO: Change for production
    }
}
