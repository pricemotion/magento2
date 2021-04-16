<?php
namespace Pricemotion\Magento2\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Monolog\Handler\StreamHandler;
use Pricemotion\Magento2\Cron;
use Pricemotion\Magento2\Logger\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends Command {
    private $state;

    private $logger;

    public function __construct(
        string $name,
        State $state,
        Logger $logger
    ) {
        $this->state = $state;
        $this->logger = $logger;
        parent::__construct($name);
    }

    public function configure() {
        $this->setDefinition(new InputDefinition([
            new InputOption('force', 'f', InputOption::VALUE_NONE),
            new InputOption('ean', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY),
        ]));
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->state->setAreaCode(Area::AREA_CRONTAB);
        $this->logger->pushHandler(new StreamHandler(STDERR));
        $job = ObjectManager::getInstance()->get(Cron\Update::class);
        $job->setTimeLimit(null);
        if ($input->getOption('force')) {
            $job->setIgnoreUpdatedAt(true);
        }
        if ($input->getOption('ean')) {
            $job->setEanFilter($input->getOption('ean'));
        }
        $this->logger->info(sprintf('Starting manual update run: %s', (string) $input));
        $job->execute();
        $this->logger->info('Completed manual update run');
        return 0;
    }
}
