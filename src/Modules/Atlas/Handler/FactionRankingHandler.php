<?php

namespace App\Modules\Atlas\Handler;

use App\Modules\Atlas\Domain\Repository\FactionRankingRepositoryInterface;
use App\Modules\Atlas\Domain\Repository\PlayerRankingRepositoryInterface;
use App\Modules\Atlas\Domain\Repository\RankingRepositoryInterface;
use App\Modules\Atlas\Manager\RankingManager;
use App\Modules\Atlas\Message\FactionRankingMessage;
use App\Modules\Atlas\Routine\FactionRoutine;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FactionRankingHandler
{
	public function __construct(
		private ColorManager                      $colorManager,
		private ColorRepositoryInterface          $colorRepository,
		private FactionRankingRepositoryInterface $factionRankingRepository,
		private PlayerRankingRepositoryInterface  $playerRankingRepository,
		private RankingRepositoryInterface        $rankingRepository,
		private SectorRepositoryInterface         $sectorRepository,
		private RankingManager                    $rankingManager,
		private string                            $serverStartTime,
		private int                               $hoursBeforeStartOfRanking,
		private int                               $pointsToWin,
	) {
	}

	public function __invoke(FactionRankingMessage $message): void
	{
		if (true === $this->rankingRepository->hasBeenAlreadyProcessed(false, true)) {
			return;
		}
		$factionRoutine = new FactionRoutine();

		$factions = $this->colorRepository->getInGameFactions();
		$sectors = $this->sectorRepository->getAll();

		$ranking = $this->rankingManager->createRanking(false, true);

		foreach ($factions as $faction) {
			$routesIncome = $this->factionRankingRepository->getRoutesIncome($faction);
			$playerRankings = $this->playerRankingRepository->getFactionPlayerRankings($faction);

			$factionRoutine->execute($faction, $playerRankings, $routesIncome, $sectors);
		}

		$winningFactionId = $factionRoutine->processResults(
			$ranking,
			$factions,
			$this->serverStartTime,
			$this->hoursBeforeStartOfRanking,
			$this->pointsToWin,
		);

		if (null !== $winningFactionId) {
			$this->rankingManager->processWinningFaction($winningFactionId);
		}
	}
}
