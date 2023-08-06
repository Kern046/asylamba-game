<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Infrastructure\DataFixtures\Factory;

use App\Modules\Gaia\Model\System;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\ModelFactory;

class SystemFactory extends ModelFactory
{
	protected function getDefaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'sector' => SectorFactory::random(),
			'faction' => null,
			'xPosition' => self::faker()->numberBetween(0, 100),
			'yPosition' => self::faker()->numberBetween(0, 100),
			'typeOfSystem' => 1,
		];
	}

	protected static function getClass(): string
	{
		return System::class;
	}
}
