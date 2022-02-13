<?php

namespace  App\Modules\Hephaistos\Ui\Cli;

use App\Shared\Application\SchedulerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'app:hephaistos:schedule-actions',
	description: 'Retrieve planned actions and set them in Messenger'
)]
class ScheduleActions extends Command
{
	/**
	 * @param list<SchedulerInterface> $schedulers
	 */
	public function __construct(protected iterable $schedulers)
	{
		parent::__construct();
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);

		foreach ($this->schedulers as $scheduler) {
			$style->info(\sprintf('Scheduling %s actions', get_class($scheduler)));

			$scheduler->schedule();
		}

		$style->success('All schedulers have been processed !');

		return self::SUCCESS;
	}
}
