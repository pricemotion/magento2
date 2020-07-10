<?php
namespace Pricemotion\Magento2\Block\Adminhtml\Catalog\Product\Edit\Tab;

use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\Constants;

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
            'token' => $this->config->getApiToken(),
            'ean' => $this->getProduct()->getData($this->config->getEanAttribute()),
            'settings' => $this->getProduct()->getData(Constants::ATTR_SETTINGS) ?: new \stdClass(),
        ]);

        return parent::_beforeToHtml();
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
