<?php
namespace Pricemotion\Magento2\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Logger\Monolog;
use Monolog\Handler\StreamHandler;
use Pricemotion\Magento2\Cron\Update as CronUpdate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends Command {
    private $job;
    private $monolog;
    private $state;

    public function __construct(
        string $name,
        CronUpdate $job,
        Monolog $monolog,
        State $state
    ) {
        $this->job = $job;
        $this->monolog = $monolog;
        $this->state = $state;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->state->setAreaCode(Area::AREA_CRONTAB);
        $this->monolog->pushHandler(new StreamHandler(STDERR));
        $this->job->execute();
    }
}