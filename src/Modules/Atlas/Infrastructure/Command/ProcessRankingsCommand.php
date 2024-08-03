<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Infrastructure\Command;

use App\Modules\Atlas\Model\Ranking;
use App\Modules\Atlas\Routine\FactionRoutineHandler;
use App\Modules\Atlas\Routine\PlayerRoutineHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
	name: 'app:atlas:process-rankings',
	description: 'Create the rankings for the current day',
)]
class ProcessRankingsCommand extends Command
{
	private SymfonyStyle $style;

	public function __construct(
		private readonly PlayerRoutineHandler $playerRoutineHandler,
		private readonly FactionRoutineHandler $factionRoutineHandler,
		private readonly EntityManagerInterface $entityManager,
	) {
		parent::__construct();
	}

	public function initialize(InputInterface $input, OutputInterface $output): void
	{
		$this->style = new SymfonyStyle($input, $output);
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$ranking = new Ranking(
			id: Uuid::v4(),
			createdAt: new \DateTimeImmutable(),
		);

		$this->entityManager->persist($ranking);
		$this->entityManager->flush();

		$this->style->info('Processing player rankings');

		$this->playerRoutineHandler->process($ranking);

		$this->style->info('Processing faction rankings');

		$this->factionRoutineHandler->process($ranking);

		$this->style->success('Ranking has been successfully generated');

		return self::SUCCESS;
	}
}
