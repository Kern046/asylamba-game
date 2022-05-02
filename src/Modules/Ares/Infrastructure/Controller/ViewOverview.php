<?php

namespace App\Modules\Ares\Infrastructure\Controller;

use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Application\Registry\CurrentPlayerBasesRegistry;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewOverview extends AbstractController
{
	public function __invoke(
		Player $currentPlayer,
		CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
		CommanderManager $commanderManager,
		OrbitalBaseManager $orbitalBaseManager,
	): Response {
		return $this->render('pages/ares/fleet/overview.html.twig', [
			'obsets' => $this->getObsets(
				$currentPlayerBasesRegistry,
				$currentPlayer,
				$commanderManager,
				$orbitalBaseManager
			),
		]);
	}

	private function getObsets(
		CurrentPlayerBasesRegistry $currentPlayerBasesRegistry,
		Player $currentPlayer,
		CommanderManager $commanderManager,
		OrbitalBaseManager $orbitalBaseManager,
	): array {
		// set d'orbitale base
		$obsets = [];
		foreach ($currentPlayerBasesRegistry->all() as $orbitalBase) {
			$obsets[] = [
				'info' => [
					'id' => $orbitalBase->getId(),
					'name' => $orbitalBase->name,
					'type' => $orbitalBase->typeOfBase,
				],
			];
		}

		// commander manager : yours
		$commanders = $commanderManager->getPlayerCommanders($currentPlayer->getId(), [Commander::AFFECTED, Commander::MOVING], ['c.rBase' => 'DESC']);

		for ($i = 0; $i < count($obsets); ++$i) {
			foreach ($commanders as $commander) {
				if ($commander->rBase == $obsets[$i]['info']['id']) {
					$obsets[$i]['fleets'][] = $commander;
				}
			}
		}
		// ship in dock
		$playerBases = $orbitalBaseManager->getPlayerBases($currentPlayer->getId());

		for ($i = 0; $i < count($obsets); ++$i) {
			foreach ($playerBases as $orbitalBase) {
				if ($orbitalBase->rPlace == $obsets[$i]['info']['id']) {
					$obsets[$i]['dock'] = $orbitalBase->shipStorage;
				}
			}
		}

		return $obsets;
	}
}
