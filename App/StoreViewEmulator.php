<?php
namespace Pricemotion\Magento2\App;

use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

class StoreViewEmulator {
    private $storeManager;

    private $emulation;

    public function __construct(StoreManagerInterface $storeManager, Emulation $emulation) {
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
    }

    public function emulate(\Closure $fn) {
        $default_store = $this->storeManager->getDefaultStoreView();

        if (!$default_store) {
            throw new ConfigurationException('No default store view is configured');
        }

        $this->emulation->startEnvironmentEmulation($default_store->getId(), Area::AREA_ADMINHTML);

        try {
            return $fn();
        } finally {
            $this->emulation->stopEnvironmentEmulation();
        }
    }
}
