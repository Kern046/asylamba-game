<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Modules\Ares\Model\Ship;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Helper\ShipHelper;
use App\Modules\Athena\Manager\ShipQueueManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ViewDocks extends AbstractController
{
	public function __invoke(
		Request $request,
		OrbitalBase $currentBase,
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		Player $currentPlayer,
		OrbitalBaseHelper $orbitalBaseHelper,
		ShipQueueRepositoryInterface $shipQueueRepository,
		TechnologyRepositoryInterface $technologyRepository,
		ShipHelper $shipHelper,
		string $dockType,
	): Response {
		if (OrbitalBase::DOCK_TYPE_MANUFACTURE === $dockType && $currentBase->levelDock1 === 0 ||
			OrbitalBase::DOCK_TYPE_SHIPYARD === $dockType && $currentBase->levelDock2 === 0) {
			return $this->redirectToRoute('base_overview');
		}

		$playerBonuses = $currentPlayerBonusRegistry->getPlayerBonus()->bonuses;

		if (OrbitalBase::DOCK_TYPE_MANUFACTURE === $dockType) {
			$dockLevel = $currentBase->levelDock1;
			$buildingNumber = OrbitalBaseResource::DOCK1;
			$dockSpeedBonus = $playerBonuses->get(PlayerBonusId::DOCK1_SPEED);
			$shipsRange = range(Ship::TYPE_PEGASE, Ship::TYPE_MEDUSE);
			$dockType = 1;
		} elseif (OrbitalBase::DOCK_TYPE_SHIPYARD === $dockType) {
			$dockLevel = $currentBase->levelDock2;
			$buildingNumber = OrbitalBaseResource::DOCK2;
			$dockSpeedBonus = $playerBonuses->get(PlayerBonusId::DOCK2_SPEED);
			$shipsRange = range(Ship::TYPE_GRIFFON, Ship::TYPE_PHENIX);
			$dockType = 2;
		} else {
			throw new BadRequestHttpException('Invalid dock type');
		}
		$shipQueues = $shipQueueRepository->getByBaseAndDockType($currentBase, $dockType);
		$nbShipQueues = count($shipQueues);
		$technology = $technologyRepository->getPlayerTechnology($currentPlayer);

		// place dans le hangar
		$totalSpace = $orbitalBaseHelper->getBuildingInfo($buildingNumber, 'level', $dockLevel, 'storageSpace');
		$storage = $currentBase->getShipStorage();
		$inStorage = 0;

		foreach ($shipsRange as $m) {
			$inStorage += ShipResource::getInfo($m, 'pev') * ($storage[$m] ?? 0);
		}

		$inQueue = 0;

		foreach ($shipQueues as $shipQueue) {
			$inQueue += ShipResource::getInfo($shipQueue->shipNumber, 'pev') * $shipQueue->quantity;
		}

		$maxShips = 0;

		return $this->render('pages/athena/docks.html.twig', [
			'building_number' => $buildingNumber,
			'technology' => $technology,
			'dock_type' => $dockType,
			'dock_level' => $dockLevel,
			'ships_range' => $shipsRange,
			'total_space' => $totalSpace,
			'ship_queues' => $shipQueues,
			'nb_ship_queues' => $nbShipQueues,
			'nb_dock_queues' => $orbitalBaseHelper->getBuildingInfo($buildingNumber, 'level', $dockLevel, 'nbQueues'),
			'in_storage' => $inStorage,
			'in_queue' => $inQueue,
			'ship_resource_refund' => $this->getParameter('athena.building.ship_queue_resource_refund'),
			'dock_speed_bonus' => $dockSpeedBonus,
			'storage' => $storage,
			'ships_data' => $this->getShipsData(
				$shipHelper,
				$currentPlayer,
				$currentBase,
				$technology,
				$shipsRange,
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
		Player $currentPlayer,
		OrbitalBase $currentBase,
		Technology $technology,
		array $range,
		int &$maxShips,
		int $nbShipQueues,
		int $totalSpace,
		int $inStorage,
		int $inQueue
	): array {
		$data = [];

		foreach ($range as $i) {
			// calcul du nombre de vaisseaux max
			$maxShipResource = floor($currentBase->resourcesStorage / ShipResource::getInfo($i, 'resourcePrice'));
			if (ColorResource::EMPIRE === $currentPlayer->faction->identifier && in_array($i, [ShipResource::CERBERE, ShipResource::PHENIX])) {
				// bonus if the player is from the Empire
				$resourcePrice = ShipResource::getInfo($i, 'resourcePrice');
				$resourcePrice -= round($resourcePrice * ColorResource::BONUS_EMPIRE_CRUISER / 100);
				$maxShipResource = floor($currentBase->resourcesStorage / $resourcePrice);
			}
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
