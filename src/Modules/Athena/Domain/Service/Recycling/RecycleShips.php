<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Recycling;

use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Shared\Application\PercentageApplier;

class RecycleShips
{
	public function __invoke(RecyclingMission $recyclingMission, int $extractionPoints): array
	{
		$shipRecycled = PercentageApplier::toInt($extractionPoints, $recyclingMission->target->coefHistory);

		// convert shipRecycled to real ships
		$pointsToRecycle = round($shipRecycled * RecyclingMission::COEF_SHIP);
		$shipsArray1 = [];
		$buyShip = [];
		for ($i = 0; $i < ShipResource::SHIP_QUANTITY; ++$i) {
			if (floor($pointsToRecycle / ShipResource::getInfo($i, 'resourcePrice')) > 0) {
				$shipsArray1[] = [
					'ship' => $i,
					'price' => ShipResource::getInfo($i, 'resourcePrice'),
					'canBuild' => true, ];
			}
			$buyShip[] = 0;
		}

		shuffle($shipsArray1);
		$shipsArray = [];
		$onlyThree = 0;
		foreach ($shipsArray1 as $key => $value) {
			++$onlyThree;
			$shipsArray[] = $value;
			if (3 == $onlyThree) {
				break;
			}
		}
		$continue = true;
		if (count($shipsArray) > 0) {
			while ($continue) {
				foreach ($shipsArray as $key => $line) {
					if ($line['canBuild']) {
						$nbmax = intval(floor($pointsToRecycle / $line['price']));
						if ($nbmax < 1) {
							$shipsArray[$key]['canBuild'] = false;
						} else {
							$qty = rand(1, $nbmax);
							$pointsToRecycle -= $qty * $line['price'];
							$buyShip[$line['ship']] += $qty;
						}
					}
				}

				$canBuild = false;
				// verify if we can build one more ship
				foreach ($shipsArray as $key => $line) {
					if ($line['canBuild']) {
						$canBuild = true;
						break;
					}
				}
				if (!$canBuild) {
					// if the 3 types of ships can't be build anymore --> stop
					$continue = false;
				}
			}
		}

		return $buyShip;
	}
}
