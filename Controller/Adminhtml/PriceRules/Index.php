<?php
namespace Pricemotion\Magento2\Controller\Adminhtml\PriceRules;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\Store;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\Helper\PriceRules as Helper;
use Magento\Catalog\Model\ResourceModel\Product\Action as ProductAction;
use Pricemotion\Magento2\Logger\Logger;

class Index extends Action implements HttpPostActionInterface, HttpGetActionInterface {

    const ADMIN_RESOURCE = 'Magento_Catalog::update_attributes';

    private $resultPageFactory;
    private $filter;
    private $collectionFactory;
    private $helper;
    private $productAction;
    private $logger;

    public function __construct(
        PageFactory $page_factory,
        Filter $filter,
        CollectionFactory $collection_factory,
        Helper $helper,
        ProductAction $product_action,
        Logger $logger,
        Action\Context $context
    ) {
        $this->resultPageFactory = $page_factory;
        $this->filter = $filter;
        $this->collectionFactory = $collection_factory;
        $this->helper = $helper;
        $this->productAction = $product_action;
        $this->logger = $logger;
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
            $product_ids = $this->helper->getProductIds();
            $this->logger->info(sprintf(
                "Updating %s attribute on %d products",
                Constants::ATTR_SETTINGS,
                sizeof($product_ids)
            ));
            $start_time = microtime(true);
            $this->productAction->updateAttributes(
                $product_ids,
                [
                    Constants::ATTR_SETTINGS => $this->_request->getParam('product_pricemotion_settings'),
                    Constants::ATTR_UPDATED_AT => null,
                ],
                $this->_request->getParam('store', Store::DEFAULT_STORE_ID)
            );
            $duration = microtime(true) - $start_time;
            $this->logger->debug(sprintf("Mass update completed in %.4f s", $duration));
            $this->helper->setProductIds([]);
            $this->messageManager->addSuccessMessage(__("Pricemotion price rules have been updated for %1 products.", sizeof($product_ids)));
            return $this->resultRedirectFactory->create()->setPath('catalog/product/', ['_current' => true]);
        }

        $page = $this->resultPageFactory->create();
        $page->getConfig()->getTitle()->prepend(__("Update Pricemotion price rules"));

        return $page;
    }

}