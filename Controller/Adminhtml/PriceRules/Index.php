<?php
namespace Pricemotion\Magento2\Controller\Adminhtml\PriceRules;

use Magento\Backend\App\Action;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\Helper\PriceRules as Helper;

class Index extends Action implements HttpPostActionInterface, HttpGetActionInterface {

    const ADMIN_RESOURCE = 'Magento_Catalog::update_attributes';

    private $resultPageFactory;
    private $filter;
    private $collectionFactory;
    private $helper;

    public function __construct(
        PageFactory $page_factory,
        Filter $filter,
        CollectionFactory $collection_factory,
        Helper $helper,
        Action\Context $context
    ) {
        $this->resultPageFactory = $page_factory;
        $this->filter = $filter;
        $this->collectionFactory = $collection_factory;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute() {
        if ($this->_request->getParam('filters')) {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $this->helper->setProductIds($collection->getAllIds());
        }

        if (!$this->helper->getProductIds()) {
            $this->messageManager->addErrorMessage(__("Please select products of which to change the Pricemotion price rules."));
            return $this->resultRedirectFactory->create()->setPath('catalog/product/', ['_current' => true]);
        }

        if ($this->_request->getParam('product_pricemotion_settings')) {
            $products = $this->helper->getProducts();
            $products->addAttributeToSelect(Constants::ATTR_SETTINGS);
            $updated = 0;
            /** @var Product $product */
            foreach ($products->getItems() as $product) {
                $product->setData(Constants::ATTR_SETTINGS, $this->_request->getParam('product_pricemotion_settings'));
                $product->save();
                $updated++;
            }
            $this->messageManager->addSuccessMessage(__("Pricemotion price rules have been updated for %1 products.", $updated));
            return $this->resultRedirectFactory->create()->setPath('catalog/product/', ['_current' => true]);
        }

        $page = $this->resultPageFactory->create();
        $page->getConfig()->getTitle()->prepend(__("Update Pricemotion price rules"));

        return $page;
    }

}