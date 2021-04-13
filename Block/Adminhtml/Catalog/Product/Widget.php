<?php
namespace Pricemotion\Magento2\Block\Adminhtml\Catalog\Product;

use Magento\Backend\Block\Template;
use Magento\Catalog\Model\Product;
use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Registry;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\Constants;

abstract class Widget extends \Magento\Backend\Block\Widget {
    protected $_template = 'widget.phtml';

    protected $config;

    private $coreRegistry;

    private $localeResolver;

    abstract protected function getWidgetPath(): string;

    abstract protected function getWidgetParameters(): \stdClass;

    public function __construct(
        Template\Context $context,
        Registry $coreRegistry,
        Config $config,
        ResolverInterface $localeResolver,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->config = $config;
        $this->localeResolver = $localeResolver;

        if (class_exists(DynamicCollector::class)) {
            $csp = ObjectManager::getInstance()->get(DynamicCollector::class);
            $csp->add(new FetchPolicy('frame-src', false, [$this->getOrigin(Constants::getWebUrl())]));
        }

        parent::__construct($context, $data);
    }

    protected function _beforeToHtml() {
        $this->assign('settings', $this->getSettings());

        return parent::_beforeToHtml();
    }

    protected function getSettings(): array {
        $parameters = $this->getWidgetParameters();
        $parameters->locale = $this->localeResolver->getLocale();

        return [
            'web_origin' => $this->getOrigin(Constants::getWebUrl()),
            'widget_url' =>
                Constants::getWebUrl() .
                $this->getWidgetPath() .
                '?' . http_build_query(['assetVersion' => Constants::getAssetVersion()]) .
                '#' . json_encode($parameters),
            'form_key' => $this->getFormKey(),
        ];
    }

    protected function getProduct(): Product {
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
