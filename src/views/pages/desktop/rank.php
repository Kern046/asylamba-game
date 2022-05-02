<?php

use App\Classes\Library\Utils;
use App\Modules\Atlas\Model\PlayerRanking;

$container = $this->getContainer();
$componentPath = $container->getParameter('component');
$request = $this->getContainer()->get('app.request');
$session = $this->getContainer()->get(\App\Classes\Library\Session\SessionWrapper::class);
$playerRankingManager = $this->getContainer()->get(\App\Modules\Atlas\Manager\PlayerRankingManager::class);
$factionRankingManager = $this->getContainer()->get(\App\Modules\Atlas\Manager\FactionRankingManager::class);

// background paralax
echo '<div id="background-paralax" class="rank"></div>';

// inclusion des elements
include 'rankElement/subnav.php';
include 'defaultElement/movers.php';

// contenu spécifique
echo '<div id="content">';
	include $componentPath.'publicity.php';

	if (!$request->query->has('view') or 'player' == $request->query->get('view')) {
		$S_PRM1 = $playerRankingManager->getCurrentSession();

		// load current player
		$playerRankingManager->newSession();
		$playerRankingManager->loadLastContext(['rPlayer' => $session->get('playerId')]);
		$p = $playerRankingManager->get();

		$generalPosition = (false === $p || 'top' === $request->query->get('mode') || $p->generalPosition - PlayerRanking::PREV < 0) ? 0 : $p->generalPosition - PlayerRanking::PREV;
		$resourcesPosition = (false === $p || 'top' === $request->query->get('mode') || $p->resourcesPosition - PlayerRanking::PREV < 0) ? 0 : $p->resourcesPosition - PlayerRanking::PREV;
		$experiencePosition = (false === $p || 'top' === $request->query->get('mode') || $p->experiencePosition - PlayerRanking::PREV < 0) ? 0 : $p->experiencePosition - PlayerRanking::PREV;
		$fightPosition = (false === $p || 'top' === $request->query->get('mode') || $p->fightPosition - PlayerRanking::PREV < 0) ? 0 : $p->fightPosition - PlayerRanking::PREV;
		$armiesPosition = (false === $p || 'top' === $request->query->get('mode') || $p->armiesPosition - PlayerRanking::PREV < 0) ? 0 : $p->armiesPosition - PlayerRanking::PREV;
		$butcherPosition = (false === $p || 'top' === $request->query->get('mode') || $p->butcherPosition - PlayerRanking::PREV < 0) ? 0 : $p->butcherPosition - PlayerRanking::PREV;
		$traderPosition = (false === $p || 'top' === $request->query->get('mode') || $p->traderPosition - PlayerRanking::PREV < 0) ? 0 : $p->traderPosition - PlayerRanking::PREV;

		// include part
		$PLAYER_RANKING_FRONT = $playerRankingManager->newSession();
		$playerRankingManager->loadLastContext([], ['generalPosition', 'ASC'], [0, 1]);
		include $componentPath.'rank/player/front.php';

		$PLAYER_RANKING_GENERAL = $playerRankingManager->newSession();
		$playerRankingManager->loadLastContext([], ['generalPosition', 'ASC'], [$generalPosition, PlayerRanking::STEP]);
		include $componentPath.'rank/player/general.php';

		$PLAYER_RANKING_XP = $playerRankingManager->newSession();
		$playerRankingManager->loadLastContext([], ['experiencePosition', 'ASC'], [$experiencePosition, PlayerRanking::STEP]);
		include $componentPath.'rank/player/xp.php';

		$PLAYER_RANKING_FIGHT = $playerRankingManager->newSession();
		$playerRankingManager->loadLastContext([], ['fightPosition', 'ASC'], [$fightPosition, PlayerRanking::STEP]);
		include $componentPath.'rank/player/fight.php';

		$PLAYER_RANKING_RESOURCES = $playerRankingManager->newSession();
		$playerRankingManager->loadLastContext([], ['resourcesPosition', 'ASC'], [$resourcesPosition, PlayerRanking::STEP]);
		include $componentPath.'rank/player/resources.php';

		$PLAYER_RANKING_ARMIES = $playerRankingManager->newSession();
		$playerRankingManager->loadLastContext([], ['armiesPosition', 'ASC'], [$armiesPosition, PlayerRanking::STEP]);
		include $componentPath.'rank/player/armies.php';

		$PLAYER_RANKING_BUTCHER = $playerRankingManager->newSession();
		$playerRankingManager->loadLastContext([], ['butcherPosition', 'ASC'], [$butcherPosition, PlayerRanking::STEP]);
		include $componentPath.'rank/player/butcher.php';

		$PLAYER_RANKING_TRADER = $playerRankingManager->newSession();
		$playerRankingManager->loadLastContext([], ['traderPosition', 'ASC'], [$traderPosition, PlayerRanking::STEP]);
		include $componentPath.'rank/player/trader.php';

		include $componentPath.'rank/player/stats.php';

		$playerRankingManager->changeSession($S_PRM1);
	} elseif ('faction' == $request->query->get('view')) {
		$S_FRM1 = $factionRankingManager->getCurrentSession();

		// include part
		$FACTION_RANKING_FRONT = $factionRankingManager->newSession();

		if (Utils::interval($container->getParameter('server_start_time'), Utils::now(), 'h') > $container->getParameter('hours_before_start_of_ranking')) {
			$factionRankingManager->loadLastContext([], ['pointsPosition', 'ASC'], [0, 1]);
		} else {
			$factionRankingManager->loadLastContext([], ['generalPosition', 'ASC'], [0, 1]);
		}

		include $componentPath.'rank/faction/front.php';

		$FACTION_RANKING_POINTS = $factionRankingManager->newSession();
		$factionRankingManager->loadLastContext([], ['pointsPosition', 'ASC']);
		include $componentPath.'rank/faction/points.php';

		$FACTION_RANKING_GENERAL = $factionRankingManager->newSession();
		$factionRankingManager->loadLastContext([], ['generalPosition', 'ASC']);
		include $componentPath.'rank/faction/general.php';

		$FACTION_RANKING_WEALTH = $factionRankingManager->newSession();
		$factionRankingManager->loadLastContext([], ['wealthPosition', 'ASC']);
		include $componentPath.'rank/faction/wealth.php';

		$FACTION_RANKING_TERRITORIAL = $factionRankingManager->newSession();
		$factionRankingManager->loadLastContext([], ['territorialPosition', 'ASC']);
		include $componentPath.'rank/faction/territorial.php';

		include $componentPath.'rank/faction/info-victory.php';

		$factionRankingManager->changeSession($S_FRM1);
	} else {
		$this->getContainer()->get('app.response')->redirect('404');
	}
echo '</div>';
