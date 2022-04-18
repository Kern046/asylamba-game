<?php

namespace App\Modules\Atlas\Infrastructure\Controller;

use App\Modules\Atlas\Manager\PlayerRankingManager;
use App\Modules\Atlas\Model\PlayerRanking;
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
		PlayerRankingManager $playerRankingManager,
		PlayerManager $playerManager,
	): Response {
		# load current player
		$playerRankingManager->newSession();
		$playerRankingManager->loadLastContext(array('rPlayer' => $currentPlayer->getId()));
		$p = $playerRankingManager->get();
		
		$positionGetter = fn (PlayerRanking|false $p, callable $positionFieldGetter) => (
			$p === FALSE ||
			$request->query->get('mode') === 'top' ||
			$positionFieldGetter($p) - PlayerRanking::PREV < 0
		) ? 0 : $positionFieldGetter($p) - PlayerRanking::PREV;

		$generalPosition 	= $positionGetter($p, fn (PlayerRanking $p) => $p->generalPosition);
		$resourcesPosition 	= $positionGetter($p, fn (PlayerRanking $p) => $p->resourcesPosition);
		$experiencePosition = $positionGetter($p, fn (PlayerRanking $p) => $p->experiencePosition);
		$fightPosition 		= $positionGetter($p, fn (PlayerRanking $p) => $p->fightPosition);
		$armiesPosition 	= $positionGetter($p, fn (PlayerRanking $p) => $p->armiesPosition);
		$butcherPosition 	= $positionGetter($p, fn (PlayerRanking $p) => $p->butcherPosition);
		$traderPosition 	= $positionGetter($p, fn (PlayerRanking $p) => $p->traderPosition);

		$playerRankingManager->newSession();
		$playerRankingManager->loadLastContext(array(), array('generalPosition', 'ASC'), array(0, 1));
		$bestPlayer = $playerRankingManager->get(0);

		$playerRankingManager->newSession();
		$playerRankingManager->loadLastContext(array(), array('generalPosition', 'ASC'), array($generalPosition, PlayerRanking::STEP));
		$generalRankings = $playerRankingManager->getAll();

		$playerRankingManager->newSession();
		$playerRankingManager->loadLastContext(array(), array('experiencePosition', 'ASC'), array($experiencePosition, PlayerRanking::STEP));
		$experienceRankings = $playerRankingManager->getAll();

		$playerRankingManager->newSession();
		$playerRankingManager->loadLastContext(array(), array('fightPosition', 'ASC'), array($fightPosition, PlayerRanking::STEP));
		$fightRankings = $playerRankingManager->getAll();

		$playerRankingManager->newSession();
		$playerRankingManager->loadLastContext(array(), array('resourcesPosition', 'ASC'), array($resourcesPosition, PlayerRanking::STEP));
		$resourcesRankings = $playerRankingManager->getAll();

		$playerRankingManager->newSession();
		$playerRankingManager->loadLastContext(array(), array('armiesPosition', 'ASC'), array($armiesPosition, PlayerRanking::STEP));
		$armiesRankings = $playerRankingManager->getAll();

		$playerRankingManager->newSession();
		$playerRankingManager->loadLastContext(array(), array('butcherPosition', 'ASC'), array($butcherPosition, PlayerRanking::STEP));
		$butcherRankings = $playerRankingManager->getAll();

		$playerRankingManager->newSession();
		$playerRankingManager->loadLastContext(array(), array('traderPosition', 'ASC'), array($traderPosition, PlayerRanking::STEP));
		$traderRankings = $playerRankingManager->getAll();
		
		return $this->render('pages/atlas/player_rankings.html.twig', [
			'best_player' => $bestPlayer,
			'general_rankings' => $generalRankings,
			'experience_rankings' => $experienceRankings,
			'fight_rankings' => $fightRankings,
			'resources_rankings' => $resourcesRankings,
			'armies_rankings' => $armiesRankings,
			'butcher_rankings' => $butcherRankings,
			'trader_rankings' => $traderRankings,
			'active_players_count' => $playerManager->countActivePlayers(),
			'all_players_count' => $playerManager->countAllPlayers(),
		]);
	}
}
