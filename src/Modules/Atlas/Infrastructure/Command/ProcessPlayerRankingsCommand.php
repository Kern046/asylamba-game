<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Infrastructure\Command;

use App\Modules\Atlas\Routine\PlayerRoutineHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'app:atlas:process-player-rankings',
	description: 'Create the player rankings for the current day',
)]
class ProcessPlayerRankingsCommand extends Command
{
	public function __construct(
		private readonly PlayerRoutineHandler $playerRoutineHandler,
	) {
		parent::__construct();
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->playerRoutineHandler->process();

		return self::SUCCESS;
	}
}
