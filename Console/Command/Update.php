<?php
namespace Pricemotion\Magento2\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Monolog\Handler\StreamHandler;
use Pricemotion\Magento2\Cron\Update as CronUpdate;
use Pricemotion\Magento2\Logger\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends Command {
    private $job;
    private $state;
    private $logger;

    public function __construct(
        string $name,
        CronUpdate $job,
        State $state,
        Logger $logger
    ) {
        $this->job = $job;
        $this->state = $state;
        $this->logger = $logger;
        parent::__construct($name);
    }

    public function configure() {
        $this->setDefinition(new InputDefinition([
            new InputOption('force', 'f', InputOption::VALUE_NONE),
        ]));
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->state->setAreaCode(Area::AREA_CRONTAB);
        $this->logger->pushHandler(new StreamHandler(STDERR));
        if ($input->getOption('force')) {
            $this->job->setIgnoreUpdatedAt(true);
        }
        $this->job->execute();
    }
}