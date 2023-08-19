<?php

namespace App\Modules\Atlas\Infrastructure\Controller;

use App\Modules\Atlas\Domain\Repository\PlayerRankingRepositoryInterface;
use App\Modules\Atlas\Domain\Repository\RankingRepositoryInterface;
use App\Modules\Atlas\Model\PlayerRanking;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewPlayerRankings extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		PlayerManager $playerManager,
		PlayerRepositoryInterface $playerRepository,
		PlayerRankingRepositoryInterface $playerRankingRepository,
		RankingRepositoryInterface $rankingRepository,
	): Response {
		// load current player
		$p = $playerRankingRepository->getPlayerLastRanking($currentPlayer);

		$positionGetter = fn (PlayerRanking|null $p, callable $positionFieldGetter) => (
			null === $p ||
			'top' === $request->query->get('mode') ||
			$positionFieldGetter($p) - PlayerRanking::PREV < 0
		) ? 0 : $positionFieldGetter($p) - PlayerRanking::PREV;

		$generalPosition = $positionGetter($p, fn (PlayerRanking $p) => $p->generalPosition);
		$resourcesPosition = $positionGetter($p, fn (PlayerRanking $p) => $p->resourcesPosition);
		$experiencePosition = $positionGetter($p, fn (PlayerRanking $p) => $p->experiencePosition);
		$fightPosition = $positionGetter($p, fn (PlayerRanking $p) => $p->fightPosition);
		$armiesPosition = $positionGetter($p, fn (PlayerRanking $p) => $p->armiesPosition);
		$butcherPosition = $positionGetter($p, fn (PlayerRanking $p) => $p->butcherPosition);
		$traderPosition = $positionGetter($p, fn (PlayerRanking $p) => $p->traderPosition);

		$ranking = $rankingRepository->getLastRanking();

		$bestPlayer = $playerRankingRepository->getBestPlayerRanking()?->player;

		$generalRankings = $playerRankingRepository->getRankingsByRange($ranking, 'generalPosition', $generalPosition, PlayerRanking::STEP);

		$experienceRankings = $playerRankingRepository->getRankingsByRange($ranking, 'experiencePosition', $experiencePosition, PlayerRanking::STEP);

		$fightRankings = $playerRankingRepository->getRankingsByRange($ranking, 'fightPosition', $fightPosition, PlayerRanking::STEP);

		$resourcesRankings = $playerRankingRepository->getRankingsByRange($ranking, 'resourcesPosition', $resourcesPosition, PlayerRanking::STEP);

		$armiesRankings = $playerRankingRepository->getRankingsByRange($ranking, 'armiesPosition', $armiesPosition, PlayerRanking::STEP);

		$butcherRankings = $playerRankingRepository->getRankingsByRange($ranking, 'butcherPosition', $butcherPosition, PlayerRanking::STEP);

		$traderRankings = $playerRankingRepository->getRankingsByRange($ranking, 'traderPosition', $traderPosition, PlayerRanking::STEP);

		return $this->render('pages/atlas/player_rankings.html.twig', [
			'best_player' => $bestPlayer,
			'general_rankings' => $generalRankings,
			'experience_rankings' => $experienceRankings,
			'fight_rankings' => $fightRankings,
			'resources_rankings' => $resourcesRankings,
			'armies_rankings' => $armiesRankings,
			'butcher_rankings' => $butcherRankings,
			'trader_rankings' => $traderRankings,
			'active_players_count' => $playerRepository->countActivePlayers(),
			'all_players_count' => $playerRepository->countAllPlayers(),
		]);
	}
}
