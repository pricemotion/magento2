<?php
namespace Pricemotion\Magento2\Block\Adminhtml\Catalog\Product\Edit\Tab;

use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;

class Pricemotion extends Template {
    protected $_template = 'product/edit/pricemotion.phtml';
    private $coreRegistry;

    public function __construct(
        Template\Context $context,
        Registry $coreRegistry,
        DynamicCollector $csp,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $csp->add(new FetchPolicy('frame-src', false, [$this->getAppUrl()]));
        parent::__construct($context, $data);
    }

    protected function _beforeToHtml() {
        $this->assign('settings', [
            'app_url' => $this->getAppUrl(),
            'ean' => $this->getProduct()->getData($this->getEanAttribute()),
            'token' => $this->getApiToken(),
        ]);

        return parent::_beforeToHtml();
    }

    private function getApiToken(): ?string {
        if (!$this->getApiKey()) {
            return null;
        }

        $expiresAt = time() + 3600;

        return $this->base64encode(implode('', [
            hash('sha256', $this->getApiKey(), true),
            hash_hmac('sha256', $expiresAt, $this->getApiKey(), true),
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

    private function getApiKey(): ?string {
        return $this->_scopeConfig->getValue('pricemotion/general/api_key');
    }

    private function getProduct(): Product {
        return $this->coreRegistry->registry('current_product');
    }

    private function getEanAttribute(): ?string {
        return $this->_scopeConfig->getValue('pricemotion/attributes/ean');
    }

    public function getViewFileUrl($fileId, array $params = []) {
        $url = parent::getViewFileUrl($fileId, $params);
        $url .= '?=' . uniqid(); // TODO: Only during development, otherwise append version
        return $url;
    }

    private function getAppUrl() {
        return 'http://localhost:8562'; // TODO: Change for production
    }
}
