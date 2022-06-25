<?php

namespace App\Modules\Zeus\Application\Handler;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Handler\Income\CommercialRouteIncomeHandler;
use App\Modules\Athena\Application\Handler\Tax\PopulationTaxHandler;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Shared\Application\PercentageApplier;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use App\Modules\Zeus\Domain\Message\PlayerCreditUpdateMessage;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerFinancialReport;
use App\Shared\Application\Handler\DurationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class PlayerCreditUpdateHandler
{
	public function __construct(
		private EntityManagerInterface $entityManager,
		private DurationHandler $durationHandler,
		private CommercialRouteIncomeHandler $commercialRouteIncomeHandler,
		private CommanderRepositoryInterface $commanderRepository,
		private OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		private PlayerRepositoryInterface $playerRepository,
		private PlayerBonusManager $playerBonusManager,
		private PopulationTaxHandler $populationTaxHandler,
		private CommanderWageHandler $commanderWageHandler,
		private ShipsWageHandler $shipsWageHandler,
		private UniversityInvestmentHandler $universityInvestmentHandler,
		private LoggerInterface $logger,
		private CurrentPlayerRegistry $currentPlayerRegistry,
		private CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		private int $gaiaId,
	) {
	}

	public function __invoke(PlayerCreditUpdateMessage $message): void
	{
		$player = $this->playerRepository->get($message->getPlayerId()) ?? throw new \RuntimeException('Player not found');
		$rebelPlayer = $this->playerRepository->get($this->gaiaId);
		$bases = $this->orbitalBaseRepository->getPlayerBases($player);
		$commanders = $this->commanderRepository->getPlayerCommanders(
			$player,
			[Commander::AFFECTED, Commander::MOVING],
			['c.experience' => 'DESC', 'c.statement' => 'ASC'],
		);
		$initialCredits = $player->credit;

		$hoursDiff = $this->durationHandler->getHoursDiff($player->uPlayer, new \DateTimeImmutable());
		if (0 === $hoursDiff) {
			return;
		}
		$this->currentPlayerRegistry->set($player);
		$bonus = $this->playerBonusManager->getBonusByPlayer($player);
		$this->currentPlayerBonusRegistry->setPlayerBonus($bonus);

		$this->entityManager->beginTransaction();

		for ($i = 0; $i < $hoursDiff; ++$i) {
			$playerFinancialReport = new PlayerFinancialReport(
				id: Uuid::v4(),
				player: $player,
				createdAt: new \DateTimeImmutable(),
				initialWallet: $player->credit,
			);

			$this->updatePlayerCredits(
				$playerFinancialReport,
				$rebelPlayer,
				$commanders,
				$bases,
			);

			$player->credit = $playerFinancialReport->getNewWallet();
			$this->entityManager->persist($playerFinancialReport);
		}

		$player->uPlayer = new \DateTimeImmutable();

		$this->entityManager->flush();
		$this->entityManager->commit();

		$this->logger->debug('{playerName} has passed from {credits} to {newCredits} credits', [
			'playerName' => $player->name,
			'credits' => $initialCredits,
			'newCredits' => $player->credit,
		]);
	}

	/**
	 * @param list<OrbitalBase> $bases
	 */
	private function updatePlayerCredits(
		PlayerFinancialReport $playerFinancialReport,
		Player $rebelPlayer,
		array $commanders,
		array $bases,
	): void {
		// Process all player bases income and losses
		foreach ($bases as $base) {
			$populationTax = $this->populationTaxHandler->getPopulationTax($base)->total;

			$routesIncome = $this->commercialRouteIncomeHandler->getCommercialRouteIncome($base)->total;

			$this->payFactionTax($base, $populationTax, $playerFinancialReport);

			$playerFinancialReport->populationTaxes += $populationTax;
			$playerFinancialReport->commercialRoutesIncome += $routesIncome;
			// TODO Handler anti spy and school investment recomputing in case of bankrupt
			$playerFinancialReport->antiSpyInvestments += $base->iAntiSpy;
			$playerFinancialReport->schoolInvestments += $base->iSchool;
		}

		$this->universityInvestmentHandler->spend($playerFinancialReport, $bases);
		$this->commanderWageHandler->payWages($playerFinancialReport, $commanders, $rebelPlayer);
		$this->shipsWageHandler->payWages($playerFinancialReport, $commanders, $bases, $rebelPlayer);
	}

	private function payFactionTax(OrbitalBase $base, int $populationTax, PlayerFinancialReport $playerFinancialReport): void
	{
		if (null === ($sectorFaction = $base->place->system->sector->faction)) {
			return;
		}

		$factionTax = $this->getFactionTax($base, $populationTax);

		$sectorFaction->increaseCredit($factionTax);

		$playerFinancialReport->factionTaxes += $factionTax;
	}

	private function getFactionTax(OrbitalBase $base, int $populationTax): int
	{
		return PercentageApplier::toInt($base->place->system->sector->tax, $populationTax);
	}
}
