<?php
use Magento\Framework\Component\ComponentRegistrar;

if (!class_exists(ComponentRegistrar::class)) {
    return;
}

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Pricemotion_Magento2',
    isset($file)
    && realpath($file) == __FILE__
    && getenv('PRICEMOTION_DEVELOPMENT') ?
    dirname($file) : __DIR__
);
