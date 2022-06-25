<?php

namespace App\Modules\Atlas\Handler;

use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Atlas\Domain\Repository\PlayerRankingRepositoryInterface;
use App\Modules\Atlas\Domain\Repository\RankingRepositoryInterface;
use App\Modules\Atlas\Manager\RankingManager;
use App\Modules\Atlas\Message\PlayerRankingMessage;
use App\Modules\Atlas\Routine\PlayerRoutine;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class PlayerRankingHandler
{
	public function __construct(
		private RankingManager                   $rankingManager,
		private RankingRepositoryInterface       $rankingRepository,
		private PlayerRepositoryInterface        $playerRepository,
		private PlayerRankingRepositoryInterface $playerRankingRepository,
		private OrbitalBaseHelper                $orbitalBaseHelper,
		private bool                             $dataAnalysis,
	) {
	}

	public function __invoke(PlayerRankingMessage $message): void
	{
		if (true === $this->rankingRepository->hasBeenAlreadyProcessed(true, false)) {
			return;
		}
		$playerRoutine = new PlayerRoutine($this->dataAnalysis);

		$players = $this->playerRepository->getByStatements([Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY]);

		// $S_PRM1 = $this->playerRankingManager->getCurrentSession();
		// $this->playerRankingManager->newSession();
		// $this->playerRankingManager->loadLastContext();

		$ranking = $this->rankingManager->createRanking(true, false);

		$playerRoutine->execute(
			$players,
			$this->playerRankingRepository->getPlayersResources(),
			$this->playerRankingRepository->getPlayersResourcesData(),
			$this->playerRankingRepository->getPlayersGeneralData(),
			$this->playerRankingRepository->getPlayersArmiesData(),
			$this->playerRankingRepository->getPlayersPlanetData(),
			$this->playerRankingRepository->getPlayersTradeRoutes(),
			$this->playerRankingRepository->getPlayersLinkedTradeRoutes(),
			$this->playerRankingRepository->getAttackersButcherRanking(),
			$this->playerRankingRepository->getDefendersButcherRanking(),
			$this->orbitalBaseHelper
		);

		$playerRoutine->processResults($ranking, $players, $this->playerRankingManager, $playerRankingRepository);

		// $this->playerRankingManager->changeSession($S_PRM1);
	}
}
