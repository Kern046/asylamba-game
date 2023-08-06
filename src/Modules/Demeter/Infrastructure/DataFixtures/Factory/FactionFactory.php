<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DataFixtures\Factory;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\ModelFactory;

class FactionFactory extends ModelFactory
{
	protected function getDefaults(): array
	{
		$identifier = self::faker()->unique()->numberBetween(1, 11);

		return [
			'id' => Uuid::v4(),
			'identifier' => $identifier,
			'alive' => true,
			'isWinner' => false,
			'credits' => 0,
			'rankingPoints' => 0,
			'points' => 0,
			'electionStatement' => Color::MANDATE,
			'regime' => ColorResource::getInfo($identifier, 'regime'),
			'isClosed' => false,
			'description' => null,
			'isInGame' => true,
			'relations' => [],
			// @TODO move that field to the future Server entity
			'victoryClaimedAt' => null,
			// @TODO get that field from the Election table
			'lastElectionHeldAt' => null,
		];
	}

	protected static function getClass(): string
	{
		return Color::class;
	}
}
