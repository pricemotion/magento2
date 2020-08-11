<?php
namespace Pricemotion\Magento2\Block\Adminhtml\Catalog\Product;

use Magento\Framework\Registry;
use Magento\Backend\Block\Template;
use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\Helper\PriceRules as Helper;

class PriceRules extends Widget {

    private $helper;

    public function __construct(
        Helper $helper,
        Template\Context $context,
        Registry $coreRegistry,
        Config $config,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $coreRegistry, $config, $data);
    }

    protected function getSettings(): array {
        return [
            'form' => [
                'action' => $this->getUrl('pricemotion/*/*', ['_current' => true]),
            ],
        ] + parent::getSettings();
    }

    protected function getWidgetPath(): string {
        return '/rulesWidget';
    }

    protected function getWidgetParameters(): \stdClass {
        return (object) [];
    }

    protected function _prepareLayout() {
        $this->getToolbar()->addChild(
            'back_button',
            \Magento\Backend\Block\Widget\Button::class,
            [
                'label' => __('Back'),
                'onclick' => 'setLocation(\'' . $this->getUrl(
                    'catalog/product/',
                    ['store' => $this->getRequest()->getParam('store', 0)]
                ) . '\')',
                'class' => 'back',
            ]
        );

        $this->getToolbar()->addChild(
            'save_button',
            \Magento\Backend\Block\Widget\Button::class,
            [
                'label' => __('Save'),
                'class' => 'save primary pricemotion-submit',
            ]
        );

        parent::_prepareLayout();
    }

}