<?php

namespace App\Modules\Hephaistos\Ui\Cli;

use App\Classes\Scheduler\CyclicActionScheduler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'app:hephaistos:execute-hourly-tasks',
	description: 'Execute all hourly tasks'
)]
class ExecuteHourlyTasks extends Command
{
	public function __construct(private CyclicActionScheduler $cyclicActionScheduler)
	{
		parent::__construct();
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->cyclicActionScheduler->executeHourlyTasks();

		return self::SUCCESS;
	}
}
