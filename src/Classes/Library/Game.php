<?php

namespace App\Classes\Library;

use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Model\CommercialRoute;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Gaia\Model\Place;
use App\Modules\Gaia\Model\System;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Model\PlayerBonusId;

class Game
{
	public const COMMERCIAL_TIME_TRAVEL = 0.2;

	// @TODO Replace by parameters
	public const ANTISPY_DISPLAY_MODE = 0;
	public const ANTISPY_LITTLE_CIRCLE = 3;
	public const ANTISPY_MIDDLE_CIRCLE = 2;
	public const ANTISPY_BIG_CIRCLE = 1;
	public const ANTISPY_OUT_OF_CIRCLE = 0;

	public static function convertPlaceType(int $type): string
	{
		return match ($type) {
			1 => 'planète tellurique',
			2 => 'géante gazeuse',
			3 => 'ruine',
			4 => 'poche de gaz',
			5 => 'ceinture d\'astéroïdes',
			6 => 'zone vide',
			default => 'rien',
		};
	}

	public static function getSizeOfPlanet(int|float $population): int
	{
		if ($population < 100) {
			return 1;
		} elseif ($population < 200) {
			return 2;
		} else {
			return 3;
		}
	}

	public static function formatCoord(int $xCoord, int $yCoord, int $planetPosition = 0, int $sectorLocation = 0): string
	{
		if ($sectorLocation > 0) {
			return '⟨'.$sectorLocation.'⟩ '.$xCoord.':'.$yCoord.':'.$planetPosition.'';
		} elseif ($planetPosition > 0) {
			return $xCoord.':'.$yCoord.':'.$planetPosition;
		} else {
			return $xCoord.':'.$yCoord;
		}
	}

	public static function resourceProduction(float $coeffRefinery, int $coeffPlanet): float
	{
		return $coeffRefinery * $coeffPlanet;
	}

	public static function getDistance(int $xa, int $xb, int $ya, int $yb): float
	{
		$distance = floor(sqrt((($xa - $xb) * ($xa - $xb)) + (($ya - $yb) * ($ya - $yb))));

		return ($distance < 1) ? 1 : $distance;
	}

	public static function getFleetSpeed(PlayerBonus|null $bonus): float
	{
		$b = null != $bonus
			? Commander::FLEETSPEED * (3 * ($bonus->bonuses->get(PlayerBonusId::SHIP_SPEED) / 100)) : 0;

		return Commander::FLEETSPEED + $b;
	}

	public static function getMaxTravelDistance($bonus): int
	{
		return Commander::DISTANCEMAX;
	}

	public static function getTimeToTravelCommercial(Place $startPlace, Place $destinationPlace, $bonus = null): float
	{
		return round(self::getTimeToTravel($startPlace, $destinationPlace, $bonus) * self::COMMERCIAL_TIME_TRAVEL);
	}

	public static function getTimeToTravel(Place $startPlace, Place $destinationPlace, PlayerBonus $bonus = null): float
	{
		// $startPlace and $destinationPlace are instance of Place
		return self::getTimeTravel(
			$startPlace->system,
			$startPlace->position,
			$destinationPlace->system,
			$destinationPlace->position,
			$bonus
		);
	}

	public static function getTimeTravelCommercial(System $systemFrom, int $positionFrom, System $systemTo, int $positionTo, PlayerBonus|null $bonus = null): float
	{
		return round(self::getTimeTravel($systemFrom, $positionFrom, $systemTo, $positionTo, $bonus) * self::COMMERCIAL_TIME_TRAVEL);
	}

	public static function getTimeTravel(System $systemFrom, int $positionFrom, System $systemTo, int $positionTo, PlayerBonus|null $bonus = null): float
	{
		return $systemFrom->id === $systemTo->id
			? Game::getTimeTravelInSystem($positionFrom, $positionTo)
			: Game::getTimeTravelOutOfSystem(
				$bonus,
				$systemFrom->xPosition,
				$systemFrom->yPosition,
				$systemTo->xPosition,
				$systemTo->yPosition,
			);
	}

	public static function getTimeTravelInSystem(int $startPosition, int $destinationPosition): float
	{
		$distance = abs($startPosition - $destinationPosition);

		return round((Commander::COEFFMOVEINSYSTEM * $distance) * ((40 - $distance) / 50) + 180);
	}

	public static function getTimeTravelOutOfSystem(PlayerBonus|null $bonus, int $startX, int $startY, int $destinationX, int $destinationY): float
	{
		$distance = self::getDistance($startX, $destinationX, $startY, $destinationY);
		$time = Commander::COEFFMOVEOUTOFSYSTEM;
		$time += round((Commander::COEFFMOVEINTERSYSTEM * $distance) / self::getFleetSpeed($bonus));

		return $time;
	}

	public static function getRCPrice(float $distance): float
	{
		return $distance * CommercialRoute::COEF_PRICE;
	}

	public static function getRCIncome(float $distance, int $bonusA = 1, int $bonusB = 1): float
	{
		$income = CommercialRoute::COEF_INCOME_2 * sqrt($distance * CommercialRoute::COEF_INCOME_1);
		$maxIncome = CommercialRoute::COEF_INCOME_2 * sqrt(100 * CommercialRoute::COEF_INCOME_1);
		if ($income > $maxIncome) {
			$income = $maxIncome;
		}

		return round($income * $bonusA * $bonusB);
	}

	public static function getAntiSpyRadius(int $investment, int $mode = self::ANTISPY_DISPLAY_MODE): float
	{
		return self::ANTISPY_DISPLAY_MODE == $mode
			// en pixels : sert à l'affichage
			? sqrt($investment / 3.14) * 20
			// en position du jeu (250x250)
			: sqrt($investment / 3.14);
	}

	public static function getAntiSpyEntryTime(Place $startPlace, Place $destinationPlace, \DateTimeImmutable $arrivalDate): array
	{
		// dans le même système
		if ($startPlace->system->id === $destinationPlace->system->id) {
			return [true, true, true];
		} else {
			$duration = self::getTimeToTravel($startPlace, $destinationPlace);

			$secRemaining = $arrivalDate->getTimestamp() - time();
			$ratioRemaining = $secRemaining / $duration;

			$distance = self::getDistance(
				$startPlace->system->xPosition,
				$destinationPlace->system->yPosition,
				$startPlace->system->xPosition,
				$destinationPlace->system->yPosition,
			);
			$distanceRemaining = $distance * $ratioRemaining;

			$antiSpyRadius = self::getAntiSpyRadius($destinationPlace->base->iAntiSpy, 1);

			if ($distanceRemaining < $antiSpyRadius / 3) {
				return [true, true, true];
			} elseif ($distanceRemaining < $antiSpyRadius / 3 * 2) {
				$ratio = ($antiSpyRadius / 3) / $distanceRemaining;
				$sec = $ratio * $secRemaining;
				$newDate = Utils::addSecondsToDate($arrivalDate, -$sec);

				return [true, true, $newDate];
			} elseif ($distanceRemaining < $antiSpyRadius) {
				$ratio = ($antiSpyRadius / 3 * 2) / $distanceRemaining;
				$sec = $ratio * $secRemaining;
				$newDate1 = Utils::addSecondsToDate($arrivalDate, -$sec);

				$ratio = ($antiSpyRadius / 3) / $distanceRemaining;
				$sec = $ratio * $secRemaining;
				$newDate2 = Utils::addSecondsToDate($arrivalDate, -$sec);

				return [true, $newDate1, $newDate2];
			} else {
				$ratio = $antiSpyRadius / $distanceRemaining;
				$sec = $ratio * $secRemaining;
				$newDate1 = Utils::addSecondsToDate($arrivalDate, -$sec);

				$ratio = ($antiSpyRadius / 3 * 2) / $distanceRemaining;
				$sec = $ratio * $secRemaining;
				$newDate2 = Utils::addSecondsToDate($arrivalDate, -$sec);

				$ratio = ($antiSpyRadius / 3) / $distanceRemaining;
				$sec = $ratio * $secRemaining;
				$newDate3 = Utils::addSecondsToDate($arrivalDate, -$sec);

				return [$newDate1, $newDate2, $newDate3];
			}
		}
	}

	public static function getCommercialShipQuantityNeeded(int $transactionType, int $quantity, int $identifier = 0): int
	{
		return match ($transactionType) {
			// 1000 ressources => 1 commercialShip
			Transaction::TYP_RESOURCE => intval(ceil($quantity / 1000)),
			// 1 PEV => 1 commercialShip
			Transaction::TYP_SHIP => (ShipResource::isAShip($identifier) and $quantity > 0)
				? $quantity * ShipResource::getInfo($identifier, 'pev')
				: throw new \LogicException('Invalid ship or quantity'),
			// 1 commander => 1 commercialShip
			Transaction::TYP_COMMANDER => 1,
			default => throw new \LogicException('Unknown transaction type'),
		};
	}

	public static function calculateCurrentRate($currentRate, $transactionType, $quantity, $identifier, $price)
	{
		// calculate the new rate (when a transaction is accepted)
		switch ($transactionType) {
			case Transaction::TYP_RESOURCE:
				// 1 resource = x credit
				$thisRate = $price / $quantity;
				// dilution of 1%
				$newRate = (($quantity * $thisRate) + (50000 * (99 * $currentRate)) / 100) / (50000 + $quantity);

				return max($newRate, Transaction::MIN_RATE_RESOURCE);
				break;
			case Transaction::TYP_SHIP:
				// 1 resource = x credit
				if (ShipResource::isAShip($identifier)) {
					$resourceQuantity = ShipResource::getInfo($identifier, 'resourcePrice') * $quantity;
					$thisRate = $price / $resourceQuantity;
					// dilution of 1%
					$newRate = (($resourceQuantity * $thisRate) + (50000 * (99 * $currentRate)) / 100) / (50000 + $resourceQuantity);

					return max($newRate, Transaction::MIN_RATE_SHIP);
				} else {
					return false;
				}
				break;
			case Transaction::TYP_COMMANDER:
				// 1 experience = x credit
				$thisRate = $price / $quantity;
				// dilution of 1%
				$newRate = (($quantity * $thisRate) + (50000 * (99 * $currentRate)) / 100) / (50000 + $quantity);

				return max($newRate, Transaction::MIN_RATE_COMMANDER);
				break;
			default:
				return 0;
				break;
		}
	}

	public static function calculateRate($transactionType, $quantity, $identifier, $price)
	{
		switch ($transactionType) {
			case Transaction::TYP_RESOURCE:
				// 1 resource = x credit
				return $price / $quantity;
				break;
			case Transaction::TYP_SHIP:
				// 1 resource = x credit
				if (ShipResource::isAShip($identifier)) {
					$resourceQuantity = ShipResource::getInfo($identifier, 'resourcePrice') * $quantity;

					return $price / $resourceQuantity;
				} else {
					return false;
				}
				break;
			case Transaction::TYP_COMMANDER:
				// 1 experience = x credit
				return $price / $quantity;
				break;
			default:
				return false;
				break;
		}
	}

	public static function getMinPriceRelativeToRate($transactionType, $quantity, $identifier = null)
	{
		switch ($transactionType) {
			case Transaction::TYP_RESOURCE:
				$minRate = Transaction::MIN_RATE_RESOURCE;
				break;
			case Transaction::TYP_SHIP:
				$minRate = Transaction::MIN_RATE_SHIP;
				$quantity = ShipResource::getInfo($identifier, 'resourcePrice') * $quantity;
				break;
			case Transaction::TYP_COMMANDER:
				$minRate = Transaction::MIN_RATE_COMMANDER;
				break;
			default:
				return false;
		}

		$price = round($quantity * $minRate);
		if ($price < 1) {
			$price = 1;
		}

		return $price;
	}

	public static function getMaxPriceRelativeToRate($transactionType, $quantity, $identifier = false)
	{
		switch ($transactionType) {
			case Transaction::TYP_RESOURCE:
				$minRate = Transaction::MAX_RATE_RESOURCE;
				break;
			case Transaction::TYP_SHIP:
				$minRate = Transaction::MAX_RATE_SHIP;
				$quantity = ShipResource::getInfo($identifier, 'resourcePrice') * $quantity;
				break;
			case Transaction::TYP_COMMANDER:
				$minRate = Transaction::MAX_RATE_COMMANDER;
				break;
			default:
				return false;
		}

		$price = $quantity * $minRate;

		return round($price);
	}

	public static function getImprovementFromScientificCoef(int $coef): int
	{
		// transform scientific coefficient of a place
		// into improvement coefficient for the technosphere
		if ($coef < 10) {
			return 0;
		} elseif ($coef >= 100) {
			return 40;
		} else {
			return intval(ceil(0.004 * $coef * $coef - 0.01 * $coef + 0.7));
		}
	}

	/**
	 * @param array<int, int> $ships
	 */
	public static function getFleetCost(array $ships, bool $affected = true): int
	{
		$cost = 0;
		for ($i = 0; $i < ShipResource::SHIP_QUANTITY; ++$i) {
			$cost += ShipResource::getInfo($i, 'cost') * ($ships[$i] ?? 0);
		}
		if (!$affected) {
			$cost *= ShipResource::COST_REDUCTION;
		}

		return ceil($cost);
	}
}
