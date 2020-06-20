<?php
namespace Pricemotion\Magento2\Block\Adminhtml\Catalog\Product\Edit\Tab;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;

class Pricemotion extends Template {
    protected $_template = 'product/edit/pricemotion.phtml';
    private $coreRegistry;

    public function __construct(
        Template\Context $context,
        Registry $coreRegistry,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    protected function _beforeToHtml() {
        $this->assign('settings', [
            'ean' => $this->getProduct()->getData($this->getEanAttribute()),
        ]);

        return parent::_beforeToHtml();
    }

    private function getProduct(): Product {
        return $this->coreRegistry->registry('current_product');
    }

    private function getEanAttribute(): ?string {
        return $this->_scopeConfig->getValue('pricemotion/attributes/ean');
    }
}
