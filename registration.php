<?php
use Magento\Framework\Component\ComponentRegistrar;

if (!class_exists('Magento\\Framework\\Component\\ComponentRegistrar')) {
    return;
}

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Pricemotion_Magento2',
    isset($file) && realpath($file) == __FILE__ && file_exists(__DIR__ . '/DEVELOPMENT.TAG') ? dirname($file) : __DIR__,
);
