<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Helper\ShipHelper;
use App\Modules\Athena\Manager\ShipQueueManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewDock1 extends AbstractController
{
	public function __invoke(
		Request $request,
		OrbitalBase $currentBase,
		Player $currentPlayer,
		ShipQueueManager $shipQueueManager,
		TechnologyManager $technologyManager,
		OrbitalBaseHelper $orbitalBaseHelper,
		ShipHelper $shipHelper,
	): Response {
		$session = $request->getSession();
		$shipQueues = $shipQueueManager->getByBaseAndDockType($currentBase->getRPlace(), 1);
		$nbShipQueues = count($shipQueues);
		$s = array('', '', '', '', '', '');
		$technology = $technologyManager->getPlayerTechnology($currentPlayer->getId());

		#place dans le hangar
		$totalSpace = $orbitalBaseHelper->getBuildingInfo(2, 'level', $currentBase->getLevelDock1(), 'storageSpace');
		$storage = $currentBase->getShipStorage();
		$inStorage = 0;

		for ($m = 0; $m < 6; $m++) {
			$inStorage += ShipResource::getInfo($m, 'pev') * $storage[$m];
		}

		$inQueue = 0;

		foreach ($shipQueues as $shipQueue) {
			$inQueue += ShipResource::getInfo($shipQueue->shipNumber, 'pev') * $shipQueue->quantity;
		}

		$maxShips = 0;

		return $this->render('pages/athena/dock1.html.twig', [
			'technology' => $technology,
			'total_space' => $totalSpace,
			'ship_queues' => $shipQueues,
			'nb_ship_queues' => $nbShipQueues,
			'nb_dock_queues' => $orbitalBaseHelper->getBuildingInfo(OrbitalBaseResource::DOCK1, 'level', $currentBase->levelDock1, 'nbQueues'),
			'in_storage' => $inStorage,
			'in_queue' => $inQueue,
			'ship_resource_refund' => $this->getParameter('athena.building.ship_queue_resource_refund'),
			'dock_speed_bonus' => $session->get('playerBonus')->get(PlayerBonus::DOCK1_SPEED),
			'storage' => $storage,
			'ships_data' => $this->getShipsData(
				$shipHelper,
				$currentBase,
				$technology,
				$maxShips,
				$nbShipQueues,
				$totalSpace,
				$inStorage,
				$inQueue,
			),
			'max_ships' => $maxShips,
		]);
	}

	private function getShipsData(
		ShipHelper $shipHelper,
		OrbitalBase $currentBase,
		Technology $technology,
		int &$maxShips,
		int $nbShipQueues,
		int $totalSpace,
		int $inStorage,
		int $inQueue
	): array {
		$data = [];

		for ($i = 0; $i < 6; $i++) {
			# calcul du nombre de vaisseaux max
			$maxShipResource = floor($currentBase->getResourcesStorage() / ShipResource::getInfo($i, 'resourcePrice'));
			$maxShipResource = ($maxShipResource < 100) ? $maxShipResource : 99;
			$maxShipPev = $totalSpace - $inStorage - $inQueue;
			$maxShipPev = floor($maxShipPev / ShipResource::getInfo($i, 'pev'));
			$maxShipPev = ($maxShipPev < 100) ? $maxShipPev : 99;
			$maxShips = ($maxShipResource <= $maxShipPev) ? $maxShipResource : $maxShipPev;

			$technologyRights = $shipHelper->haveRights($i, 'techno', $technology);

			$data[$i] = [
				'max_ships' => $maxShips,
				'has_technology_requirements' => $technologyRights,
				'missing_technology' => (true !== $technologyRights) ? $technologyRights : null,
				'has_ship_tree_requirements' => $shipHelper->haveRights($i, 'shipTree', $currentBase),
				'dock_needed_level' => $shipHelper->dockLevelNeededFor($i),
				'has_ship_queue_requirements' => $shipHelper->haveRights($i, 'queue', $currentBase, $nbShipQueues),
			];
		}

		return $data;
	}
}
