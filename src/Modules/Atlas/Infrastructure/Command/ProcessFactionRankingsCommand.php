<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Infrastructure\Command;

use App\Modules\Atlas\Routine\FactionRoutineHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'app:atlas:process-faction-rankings',
	description: 'Create the faction rankings for the current day',
)]
class ProcessFactionRankingsCommand extends Command
{
	public function __construct(
		private readonly FactionRoutineHandler $factionRoutineHandler,
	) {
		parent::__construct();
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->factionRoutineHandler->process();

		return self::SUCCESS;
	}
}
