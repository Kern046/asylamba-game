<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Infrastructure\DataFixtures\Factory;

use App\Modules\Gaia\Model\Sector;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\ModelFactory;

class SectorFactory extends ModelFactory
{
	protected function getDefaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'identifier' => self::faker()->randomNumber(2),
			'faction' => null,
			'xPosition' => self::faker()->numberBetween(0, 100),
			'yPosition' => self::faker()->numberBetween(0, 100),
			'xBarycentric' => self::faker()->numberBetween(0, 100),
			'yBarycentric' => self::faker()->numberBetween(0, 100),
			'tax' => 5,
			'name' => null,
			'points' => self::faker()->numberBetween(1, 5),
			'population' => 0,
			'lifePlanet' => 0,
			'prime' => false,
		];
	}

	protected static function getClass(): string
	{
		return Sector::class;
	}
}
