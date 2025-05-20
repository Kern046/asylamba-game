<?php

namespace App\Modules\Zeus\Model;

use App\Modules\Promethee\Model\TechnologyId;

class PlayerBonusId
{
	public const GENERATOR_SPEED = 0;
	public const REFINERY_REFINING = 1;
	public const REFINERY_STORAGE = 2;
	public const DOCK1_SPEED = 3;
	public const DOCK2_SPEED = 4;
	public const TECHNOSPHERE_SPEED = 5;
	public const COMMERCIAL_INCOME = 6;
	public const GRAVIT_MODULE = 7;
	public const DOCK3_SPEED = 8;
	public const POPULATION_TAX = 9;
	public const COMMANDER_INVEST = 10;
	public const UNI_INVEST = 11;
	public const ANTISPY_INVEST = 12;
	public const SHIP_SPEED = 13;
	public const SHIP_CONTAINER = 14;
	public const BASE_QUANTITY = 15;
	public const FIGHTER_SPEED = 16;
	public const FIGHTER_ATTACK = 17;
	public const FIGHTER_DEFENSE = 18;
	public const CORVETTE_SPEED = 19;
	public const CORVETTE_ATTACK = 20;
	public const CORVETTE_DEFENSE = 21;
	public const FRIGATE_SPEED = 22;
	public const FRIGATE_ATTACK = 23;
	public const FRIGATE_DEFENSE = 24;
	public const DESTROYER_SPEED = 25;
	public const DESTROYER_ATTACK = 26;
	public const DESTROYER_DEFENSE = 27;

	public const BONUSES_IDS = [
		self::GENERATOR_SPEED,
		self::REFINERY_REFINING,
		self::REFINERY_STORAGE,
		self::DOCK1_SPEED,
		self::DOCK2_SPEED,
		self::TECHNOSPHERE_SPEED,
		self::COMMERCIAL_INCOME,
		self::GRAVIT_MODULE,
		self::DOCK3_SPEED,
		self::POPULATION_TAX,
		self::COMMANDER_INVEST,
		self::UNI_INVEST,
		self::ANTISPY_INVEST,
		self::SHIP_SPEED,
		self::SHIP_CONTAINER,
		self::BASE_QUANTITY,
		self::FIGHTER_SPEED,
		self::FIGHTER_ATTACK,
		self::FIGHTER_DEFENSE,
		self::CORVETTE_SPEED,
		self::CORVETTE_ATTACK,
		self::CORVETTE_DEFENSE,
		self::FRIGATE_SPEED,
		self::FRIGATE_ATTACK,
		self::FRIGATE_DEFENSE,
		self::DESTROYER_SPEED,
		self::DESTROYER_ATTACK,
		self::DESTROYER_DEFENSE,
	];

	public static function isBonusId(int $bonusId): bool
	{
		return in_array($bonusId, self::BONUSES_IDS);
	}

	public static function fromTechnologyIdentifier(int $technologyIdentifier): int
	{
		return match ($technologyIdentifier) {
			TechnologyId::GENERATOR_SPEED => self::GENERATOR_SPEED,
			TechnologyId::REFINERY_REFINING => self::REFINERY_REFINING,
			TechnologyId::REFINERY_STORAGE => self::REFINERY_STORAGE,
			TechnologyId::DOCK1_SPEED => self::DOCK1_SPEED,
			TechnologyId::DOCK2_SPEED => self::DOCK2_SPEED,
			TechnologyId::TECHNOSPHERE_SPEED => self::TECHNOSPHERE_SPEED,
			TechnologyId::COMMERCIAL_INCOME => self::COMMERCIAL_INCOME,
			TechnologyId::GRAVIT_MODULE => self::GRAVIT_MODULE,
			TechnologyId::DOCK3_SPEED => self::DOCK3_SPEED,
			TechnologyId::POPULATION_TAX => self::POPULATION_TAX,
			TechnologyId::COMMANDER_INVEST => self::COMMANDER_INVEST,
			TechnologyId::UNI_INVEST => self::UNI_INVEST,
			TechnologyId::ANTISPY_INVEST => self::ANTISPY_INVEST,
			TechnologyId::SPACESHIPS_SPEED => self::SHIP_SPEED,
			TechnologyId::SPACESHIPS_CONTAINER => self::SHIP_CONTAINER,
			TechnologyId::BASE_QUANTITY => self::BASE_QUANTITY,
			TechnologyId::FIGHTER_SPEED => self::FIGHTER_SPEED,
			TechnologyId::FIGHTER_ATTACK => self::FIGHTER_ATTACK,
			TechnologyId::FIGHTER_DEFENSE => self::FIGHTER_DEFENSE,
			TechnologyId::CORVETTE_SPEED => self::CORVETTE_SPEED,
			TechnologyId::CORVETTE_ATTACK => self::CORVETTE_ATTACK,
			TechnologyId::CORVETTE_DEFENSE => self::CORVETTE_DEFENSE,
			TechnologyId::FRIGATE_SPEED => self::FRIGATE_SPEED,
			TechnologyId::FRIGATE_ATTACK => self::FRIGATE_ATTACK,
			TechnologyId::FRIGATE_DEFENSE => self::FRIGATE_DEFENSE,
			TechnologyId::DESTROYER_SPEED => self::DESTROYER_SPEED,
			TechnologyId::DESTROYER_ATTACK => self::DESTROYER_ATTACK,
			TechnologyId::DESTROYER_DEFENSE => self::DESTROYER_DEFENSE,
			default => throw new \RuntimeException(sprintf('Technology with ID %d has no associated bonus', $technologyIdentifier)),
		};
	}
}
