<?php
namespace Pricemotion\Magento2\Console\Command;

use Magento\Framework\Logger\Monolog;
use Monolog\Handler\StreamHandler;
use Pricemotion\Magento2\Cron\Update as CronUpdate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends Command {
    private $job;

    public function __construct(
        string $name,
        CronUpdate $job,
        Monolog $monolog
    ) {
        $this->job = $job;
        $monolog->pushHandler(new StreamHandler(STDERR));
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->job->execute();
    }
}