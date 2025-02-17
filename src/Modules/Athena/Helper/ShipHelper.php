<?php

namespace App\Modules\Athena\Helper;

use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Domain\Service\Base\Ship\CountAffordableShips;
use App\Modules\Athena\Domain\Service\Base\Ship\CountMaxShipQueues;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Shared\Application\PercentageApplier;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;

readonly class ShipHelper
{
	public function __construct(
		private CountMaxShipQueues $countMaxShipQueues,
		private CountAffordableShips $countAffordableShips,
		private CurrentPlayerRegistry $currentPlayerRegistry,
		private TechnologyHelper $technologyHelper,
		private OrbitalBaseHelper $orbitalBaseHelper,
		private ShipQueueRepositoryInterface $shipQueueRepository,
	) {
	}

	/**
	 * TODO Refactor with Specification Pattern
	 */
	public function haveRights(int $shipId, string $type, $sup, int $quantity = 1): bool|string
	{
		if (ShipResource::isAShip($shipId)) {
			switch ($type) {
				// assez de ressources pour construire ?
				case 'resource':
					$price = ShipResource::getInfo($shipId, 'resourcePrice') * $quantity;
					if (
						ColorResource::EMPIRE === $this->currentPlayerRegistry->get()->faction->identifier
						&& in_array($shipId, [ShipResource::CERBERE, ShipResource::PHENIX])
					) {
						$price -= PercentageApplier::toInt($price, ColorResource::BONUS_EMPIRE_CRUISER);
					}

					return !($sup < $price);
				case 'queue':
					return $quantity < ($this->countMaxShipQueues)(
						orbitalBase: $sup,
						dockType: DockType::fromShipIdentifier($shipId),
					);
					// droit de construire le vaisseau ?
					// $sup est un objet de type OrbitalBase
				case 'shipTree':
					if (ShipResource::isAShipFromDock1($shipId)) {
						$level = $sup->levelDock1;

						return $shipId < $this->orbitalBaseHelper->getBuildingInfo(2, 'level', $level, 'releasedShip');
					} elseif (ShipResource::isAShipFromDock2($shipId)) {
						$level = $sup->levelDock2;

						return ($shipId - 6) < $this->orbitalBaseHelper->getBuildingInfo(3, 'level', $level, 'releasedShip');
					} else {
						$level = $sup->levelDock3;

						return ($shipId - 12) < $this->orbitalBaseHelper->getBuildingInfo(4, 'level', $level, 'releasedShip');
					}
					// assez de pev dans le storage et dans la queue ?
					// $sup est un objet de type OrbitalBase
				case 'pev':
					if (ShipResource::isAShipFromDock1($shipId)) {
						// place dans le hangar
						$totalSpace = $this->orbitalBaseHelper->getBuildingInfo(2, 'level', $sup->levelDock1, 'storageSpace');
						// ce qu'il y a dans le hangar
						$storage = $sup->shipStorage;
						$inStorage = 0;
						for ($i = 0; $i < 6; ++$i) {
							$inStorage += ShipResource::getInfo($i, 'pev') * ($storage[$i] ?? 0);
						}
						// ce qu'il y a dans la queue
						$inQueue = 0;
						$shipQueues = $this->shipQueueRepository->getByBaseAndDockType($sup, 1);
						foreach ($shipQueues as $shipQueue) {
							$inQueue += ShipResource::getInfo($shipQueue->shipNumber, 'pev') * $shipQueue->quantity;
						}
						// ce qu'on veut rajouter
						$wanted = ShipResource::getInfo($shipId, 'pev') * $quantity;
						// comparaison
						return $wanted + $inQueue + $inStorage <= $totalSpace;
					} elseif (ShipResource::isAShipFromDock2($shipId)) {
						// place dans le hangar
						$totalSpace = $this->orbitalBaseHelper->getBuildingInfo(3, 'level', $sup->levelDock2, 'storageSpace');
						// ce qu'il y a dans le hangar
						$storage = $sup->shipStorage;
						$inStorage = 0;
						for ($i = 6; $i < 12; ++$i) {
							$inStorage += ShipResource::getInfo($i, 'pev') * ($storage[$i] ?? 0);
						}
						// ce qu'il y a dans la queue
						$inQueue = 0;
						$shipQueues = $this->shipQueueRepository->getByBaseAndDockType($sup, 2);
						foreach ($shipQueues as $shipQueue) {
							$inQueue += ShipResource::getInfo($shipQueue->shipNumber, 'pev') * 1;
						}
						// ce qu'on veut rajouter
						$wanted = ShipResource::getInfo($shipId, 'pev') * $quantity;
						// comparaison
						return $wanted + $inQueue + $inStorage <= $totalSpace;
					}
					return true;
					// a la technologie nécessaire pour constuire ce vaisseau ?
					// $sup est un objet de type Technology
				case 'techno':
					if (1 == $sup->getTechnology(ShipResource::getInfo($shipId, 'techno'))) {
						return true;
					}
					return 'il vous faut développer la technologie ' . $this->technologyHelper->getInfo(ShipResource::getInfo($shipId, 'techno'), 'name');
				default:
					throw new \ErrorException('type invalide dans haveRights de ShipResource');
			}
		} else {
			throw new \ErrorException('shipId invalide (entre 0 et 14) dans haveRights de ShipResource');
		}
	}

	public function dockLevelNeededFor($shipId)
	{
		if (ShipResource::isAShipFromDock1($shipId)) {
			$building = OrbitalBaseResource::DOCK1;
			$size = 40;
			++$shipId;
		} elseif (ShipResource::isAShipFromDock2($shipId)) {
			$building = OrbitalBaseResource::DOCK2;
			$size = 20;
			$shipId -= 5;
		} else {
			$building = OrbitalBaseResource::DOCK3;
			$size = 10;
			$shipId -= 11;
		}
		for ($i = 0; $i <= $size; ++$i) {
			$relasedShip = $this->orbitalBaseHelper->getBuildingInfo($building, 'level', $i, 'releasedShip');
			if ($relasedShip == $shipId) {
				return $i;
			}
		}
	}
}
