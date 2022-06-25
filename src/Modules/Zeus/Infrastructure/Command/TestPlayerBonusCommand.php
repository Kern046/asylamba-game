<?php

namespace App\Modules\Zeus\Infrastructure\Command;

use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'app:zeus:test-player-bonus',
	description: 'Test the modified value for a given bonus ID',
)]
class TestPlayerBonusCommand extends Command
{
	public function __construct(
		private readonly BonusApplierInterface $bonusApplier,
		private readonly PlayerBonusManager $playerBonusManager,
		private readonly PlayerRepositoryInterface $playerRepository,
	) {
		parent::__construct();
	}

	public function configure(): void
	{
		$this->addOption('bonus-id', null, InputOption::VALUE_REQUIRED, 'The Player Bonus ID to test')
			->addOption('player-id', null, InputOption::VALUE_REQUIRED, 'The Player ID to test')
			->addArgument('value', InputArgument::REQUIRED, 'The value to test');
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$bonusId = $input->getOption('bonus-id') ?? throw new \RuntimeException('Missing player bonus ID');
		$playerId = $input->getOption('player-id') ?? throw new \RuntimeException('Missing player ID');
		$value = $input->getArgument('value');

		$player = $this->playerRepository->get($playerId) ?? throw new \RuntimeException('Player not found');
		$playerBonus = $this->playerBonusManager->getBonusByPlayer($player);

		$output->writeln(sprintf(
			'The result value is %d with a %d modifier',
			$this->bonusApplier->apply($value, $bonusId, $playerBonus),
			$playerBonus->bonuses->get($bonusId),
		));

		return self::SUCCESS;
	}
}
