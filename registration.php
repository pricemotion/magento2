<?php
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Pricemotion_Magento2',
    isset($file)
    && realpath($file) == __FILE__
    && !empty($_SERVER['PRICEMOTION_DEVELOPMENT']) ?
    dirname($file) : __DIR__
);
