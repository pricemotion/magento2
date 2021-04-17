<?php
namespace Pricemotion\Magento2\Helper;

use Magento\Backend\Model\Session;

class PriceRules {
    private $session;

    public function __construct(Session $session) {
        $this->session = $session;
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
}
