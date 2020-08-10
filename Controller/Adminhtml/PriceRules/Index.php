<?php
namespace Pricemotion\Magento2\Controller\Adminhtml\PriceRules;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class Index extends Action implements HttpPostActionInterface {

    const ADMIN_RESOURCE = 'Magento_Catalog::update_attributes';

    private $resultPageFactory;
    private $filter;
    private $collectionFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $page_factory,
        Filter $filter,
        CollectionFactory $collection_factory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $page_factory;
        $this->filter = $filter;
        $this->collectionFactory = $collection_factory;
    }

    public function execute() {
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        $page = $this->resultPageFactory->create();
        $page->getConfig()->getTitle()->prepend(__("Update Pricemotion price rules"));

        return $page;
    }

}