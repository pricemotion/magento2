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
        $csp->add(new FetchPolicy('frame-src', false, [$this->getOrigin(Constants::getWebUrl())]));
        parent::__construct($context, $data);
    }

    protected function _beforeToHtml() {
        $this->assign('settings', [
            'web_url' => Constants::getWebUrl(),
            'web_origin' => $this->getOrigin(Constants::getWebUrl()),
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
        $url .= '?v=' . Constants::getAssetVersion();
        return $url;
    }

    private function getOrigin(string $url): string {
        $port = parse_url($url, PHP_URL_PORT);
        return sprintf(
            '%s://%s%s',
            parse_url($url, PHP_URL_SCHEME),
            parse_url($url, PHP_URL_HOST),
            $port ? ":$port" : ''
        );
    }
}
