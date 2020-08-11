<?php
namespace Pricemotion\Magento2\Helper;

use Magento\Backend\Model\Session;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class PriceRules {

    private $session;
    private $product_collection_factory;

    public function __construct(
        Session $session,
        CollectionFactory $product_collection_factory
    ) {
        $this->session = $session;
        $this->product_collection_factory = $product_collection_factory;
    }

    /** @param int[] $productIds */
    public function setProductIds(array $productIds): void {
        $this->session->setProductIds($productIds);
    }

    /** @return int[] */
    public function getProductIds(): array {
        $result = $this->session->getProductIds();
        if (!is_array($result)) {
            return [];
        }
        return $result;
    }

    public function getProducts(): Collection {
        $productsIds = $this->getProductIds();

        if (!$productsIds) {
            $productsIds = [0];
        }

        return $this->product_collection_factory
            ->create()
            ->addIdFilter($productsIds);
    }

}