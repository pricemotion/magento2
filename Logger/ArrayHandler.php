<?php
namespace Pricemotion\Magento2\Logger;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class ArrayHandler extends AbstractProcessingHandler {
    private $messages = [];

    public function __construct($level = Logger::DEBUG, $bubble = true) {
        parent::__construct($level, $bubble);
    }

    protected function write(array $record) {
        $this->messages[] = (string) $record['formatted'];
    }

    public function getMessages(): array {
        return $this->messages;
    }
}
