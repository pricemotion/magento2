<?php
namespace Pricemotion\Magento2\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base {
    protected $fileName = 'var/log/pricemotion.log';

    protected $loggerType = Logger::INFO;
}
