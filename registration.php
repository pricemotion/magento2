<?php
use Magento\Framework\Component\ComponentRegistrar;

if (!class_exists('Magento\\Framework\\Component\\ComponentRegistrar')) {
    return;
}

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Pricemotion_Magento2', __DIR__);
