<?php
namespace Pricemotion\Magento2\Logger;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base {
    protected $fileName = 'var/log/pricemotion.log';
}