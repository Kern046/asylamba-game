<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DataFixtures\Factory;

use App\Modules\Demeter\Model\Color;
use Zenstruck\Foundry\ModelFactory;

class FactionFactory extends ModelFactory
{
	protected function getDefaults(): array
	{
		return [

		];
	}

	protected static function getClass(): string
	{
		return Color::class;
	}
}
