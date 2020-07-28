<?php
namespace Pricemotion\Magento2\Observer;

use Magento\Catalog\Api\Data\CostInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\Product;
use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\Logger\Logger;
use Pricemotion\Magento2\App\Config;

class ProductSave implements ObserverInterface {

    private $logger;
    private $eanAttribute;
    private $priceAttribute;
    private $listPriceAttribute;

    public function __construct(
        Logger $logger,
        Config $config
    ) {
        $this->logger = $logger;
        $this->eanAttribute = $config->getEanAttribute();
        $this->priceAttribute = $config->getPriceAttribute();
        $this->listPriceAttribute = $config->getListPriceAttribute();
    }

    public function execute(Observer $observer) {
        $product = $observer->getData('entity');

        if (!$product instanceof Product) {
            return;
        }

        if (!$product->getId()) {
            return;
        }

        if (
            ($changed_attributes = $this->changed(
                $product,
                $this->priceAttribute,
                $this->listPriceAttribute,
                CostInterface::COST,
                Constants::ATTR_SETTINGS
            ))
            && !$this->changed($product, Constants::ATTR_UPDATED_AT)
        ) {
            $this->logger->debug(sprintf(
                "Attributes %s changed on product %d; resetting update timestamp...",
                implode(', ', $changed_attributes),
                $product->getId()
            ));
            $product->setData(Constants::ATTR_UPDATED_AT, null);
        }

        if ($this->changed($product, $this->eanAttribute)) {
            $this->logger->debug(sprintf(
                "EAN changed on product %d; resetting uptime timestamp and lowest price...",
                $product->getId()
            ));
            $product->setData(Constants::ATTR_UPDATED_AT, null);
            $product->setData(Constants::ATTR_LOWEST_PRICE, null);
        }

        $this->setLowestPriceRatio($product);
    }

    private function setLowestPriceRatio(Product $product) {
        if (!$this->priceAttribute
            || !$product->hasData($this->priceAttribute)
            || !$product->hasData(Constants::ATTR_LOWEST_PRICE)
        ) {
            return;
        }

        $result = null;

        if (($price = (float) $product->getData($this->priceAttribute))
            && ($lowest_price = (float) $product->getData(Constants::ATTR_LOWEST_PRICE))
        ) {
            $result = $price / $lowest_price;
        }

        $product->setData(Constants::ATTR_LOWEST_PRICE_RATIO, $result);
    }

    private function changed(Product $product, string ...$attributes) {
        $result = [];
        foreach ($attributes as $attribute) {
            if (!$product->hasData($attribute)) {
                continue;
            }
            $new_value = $product->getData($attribute);
            $old_value = $product->getOrigData($attribute);
            if ($attribute == Constants::ATTR_SETTINGS) {
                $new_value = $this->decodeJSON($new_value);
                $old_value = $this->decodeJSON($old_value);
            }
            if ($new_value != $old_value) {
                $result[] = $attribute;
            }
        }
        return $result;
    }

    private function decodeJSON($value) {
        if (!is_string($value)) {
            return $value;
        }
        return json_decode($value, true);
    }

}
