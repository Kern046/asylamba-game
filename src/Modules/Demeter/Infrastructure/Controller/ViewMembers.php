<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Library\Utils;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewMembers extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ColorManager $colorManager,
		PlayerManager $playerManager,
	): Response {
		$faction = $colorManager->get($currentPlayer->getRColor());

		$onlinePlayersCount = 0;
		$offlinePlayersCount = 0;

		$victoriesCount = 0;
		$defeatsCount = 0;
		$pointsCount = 0;
		$playersCount = 0;

		$playersByType = [
			'Gouvernement' => [],
			'Sénat' => [],
			'Peuple' => [],
		];
		$factionPlayers = $playerManager->getFactionPlayersByRanking($faction->getId());

		foreach ($factionPlayers as $factionPlayer) {
			if (Utils::interval(Utils::now(), $factionPlayer->getDLastActivity(), 's') < 600) {
				++$onlinePlayersCount;
			} else {
				++$offlinePlayersCount;
			}

			++$playersCount;
			$victoriesCount += $factionPlayer->getVictory();
			$defeatsCount += $factionPlayer->getDefeat();
			$pointsCount += $factionPlayer->getExperience();

			$type = match ($factionPlayer->getStatus()) {
				Player::STANDARD => 'Peuple',
				Player::PARLIAMENT => 'Sénat',
				default => 'Gouvernement',
			};
			$playersByType[$type][] = $factionPlayer;
		}

		return $this->render('pages/demeter/faction/members.html.twig', [
			'faction' => $faction,
			'players_by_type' => $playersByType,
			'players_count' => $playersCount,
			'last_faction_players' => $playerManager->getLastFactionPlayers($faction->id),
			'online_players_count' => $onlinePlayersCount,
			'offline_players_count' => $offlinePlayersCount,
			'victories_average' => $victoriesCount,
			'defeats_average' => $defeatsCount,
			'points_average' => $pointsCount,
		]);
	}
}
