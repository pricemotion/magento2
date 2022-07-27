<?php
namespace Pricemotion\Magento2\Block\Adminhtml\Catalog\Product;

use Magento\Backend\Block\Template;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\AbstractBlock;
use Pricemotion\Magento2\App\Config;

/** @phan-suppress-next-line PhanUnreferencedClass */
class PriceRules extends Widget {
    public function __construct(
        Template\Context $context,
        Registry $coreRegistry,
        Config $config,
        ResolverInterface $locale_resolver,
        array $data = []
    ) {
        parent::__construct($context, $coreRegistry, $config, $locale_resolver, $data);
    }

    protected function getSettings(): array {
        return [
            'form' => [
                'action' => $this->getUrl('pricemotion/*/*', [
                    '_current' => true,
                ]),
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
        $toolbar = $this->getToolbar();

        if ($toolbar instanceof AbstractBlock) {
            $toolbar->addChild('back_button', \Magento\Backend\Block\Widget\Button::class, [
                'label' => __('Back'),
                'onclick' =>
                    'setLocation(\'' .
                    $this->getUrl('catalog/product/', [
                        'store' => $this->getRequest()->getParam('store', 0),
                    ]) .
                    '\')',
                'class' => 'back',
            ]);

            $toolbar->addChild('save_button', \Magento\Backend\Block\Widget\Button::class, [
                'label' => __('Save'),
                'class' => 'save primary pricemotion-submit',
            ]);
        }

        return parent::_prepareLayout();
    }
}
