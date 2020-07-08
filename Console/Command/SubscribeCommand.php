<?php
namespace Pricemotion\Magento2\Console\Command;

use Pricemotion\Magento2\Cron\Subscribe;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SubscribeCommand extends Command {
    private $job;

    public function __construct(
        string $name,
        Subscribe $job
    ) {
        $this->job = $job;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->job->execute();
    }
}