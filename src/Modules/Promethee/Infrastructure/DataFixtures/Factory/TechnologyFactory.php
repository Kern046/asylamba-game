<?php

declare(strict_types=1);

namespace App\Modules\Promethee\Infrastructure\DataFixtures\Factory;

use App\Modules\Promethee\Model\Technology;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\ModelFactory;

class TechnologyFactory extends ModelFactory
{
	protected function getDefaults(): array
	{
		return [
			'id' => Uuid::v4(),
			'player' => PlayerFactory::random(),
		];
	}

	protected static function getClass(): string
	{
		return Technology::class;
	}
}
