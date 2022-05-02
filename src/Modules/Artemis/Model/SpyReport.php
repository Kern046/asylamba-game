<?php

/**
 * SpyReport.
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @update 26.03.14
 */

namespace App\Modules\Artemis\Model;

class SpyReport
{
	// constants
	public const TYP_NOT_CAUGHT = 0;
	public const TYP_ANONYMOUSLY_CAUGHT = 1;
	public const TYP_CAUGHT = 2;

	public const STEP_RESOURCES = 10;
	public const STEP_FLEET = 20;
	public const STEP_ANITSPY = 30;
	public const STEP_COMMANDER = 40;
	public const STEP_RC = 50;
	public const STEP_PEV = 60;
	public const STEP_POINT = 70;
	public const STEP_MOVEMENT = 80;
	public const STEP_ARMY = 95;
	public const STEP_DOCK = 99;

	// attributes
	public $id = 0;
	public $rPlayer = null;
	public $price;
	public $rPlace;
	public $placeColor = null;

	public $typeOfBase; // 0=empty, 1=ms1, 2=ms2, 3=ms3, 4=ob
	public $typeOfOrbitalBase; // 0=neutral, 1=commercial, 2=military, 3=capital
	public $placeName;
	public $points;

	public $rEnemy;
	public $enemyName;
	public $enemyAvatar;
	public $enemyLevel;

	public $resources; // from place OR base
	public $shipsInStorage;
	public $antiSpyInvest;
	public $commercialRouteIncome;
	public $commanders;

	public $success; // from 0 to 100
	public $type; // see constants
	public $dSpying;

	// additional attributes
	// from place
	public $typeOfPlace;
	public $position;
	public $population;
	public $coefResources;
	public $coefHistory;
	// from system
	public $rSector;
	public $xPosition;
	public $yPosition;
	public $typeOfSystem;

	public function getId()
	{
		return $this->id;
	}

	public function isNotCaught(): bool
	{
		return self::TYP_NOT_CAUGHT === $this->type;
	}

	public function isAnonymouslyCaught(): bool
	{
		return self::TYP_ANONYMOUSLY_CAUGHT === $this->type;
	}

	public function isCaught(): bool
	{
		return self::TYP_CAUGHT === $this->type;
	}

	public function hasSpottedFleets(): bool
	{
		return self::STEP_FLEET < $this->success;
	}

	public function hasSpottedArmies(): bool
	{
		return self::STEP_ARMY < $this->success;
	}

	public function hasSpottedMovements(): bool
	{
		return self::STEP_MOVEMENT < $this->success;
	}

	public function hasSpottedCommanders(): bool
	{
		return self::STEP_COMMANDER < $this->success;
	}

	public function hasSpottedPevs(): bool
	{
		return self::STEP_PEV < $this->success;
	}

	public function hasSpottedDocks(): bool
	{
		return self::STEP_DOCK < $this->success;
	}

	public function hasSpottedResourcesStorage(): bool
	{
		return self::STEP_RESOURCES < $this->success;
	}

	public function hasSpottedPoints(): bool
	{
		return self::STEP_POINT < $this->success;
	}

	public function hasSpottedAntiSpy(): bool
	{
		return self::STEP_ANITSPY < $this->success;
	}

	public function hasSpottedCommercialRoutesIncome(): bool
	{
		return self::STEP_RC < $this->success;
	}

	public function hasShipsInStorage(): bool
	{
		return \array_sum(\unserialize($this->shipsInStorage)) > 0;
	}
}
