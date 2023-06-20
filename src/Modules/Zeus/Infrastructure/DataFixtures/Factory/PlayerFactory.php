<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\DataFixtures\Factory;

use App\Modules\Zeus\Model\Player;
use Zenstruck\Foundry\ModelFactory;

class PlayerFactory extends ModelFactory
{
	protected function getDefaults(): array
	{
		return [
			'id' => 0,
			'bind' => self::faker()->md5(),
			'faction' => null,
			'godFather' => null,
			'name' => self::faker()->userName(),
			'sex' => 0,
			'description' => '',
			'avatar' => 't3-c4',
			'status' => Player::STANDARD,
			'credit' => 0,
			'experience' => 0,
			'factionPoint' => 0,
			'level' => 1,
			'victory' => 0,
			'defeat' => 0,
			'stepTutorial' => 1,
			'stepDone' => false,
			'iUniversity' => 5000,
			'partNaturalSciences' => 25,
			'partLifeSciences' => 25,
			'partSocialPoliticalSciences' => 25,
			'partInformaticEngineering' => 25,
			'uPlayer' => null,
			'dInscription' => null,
			'dLastConnection' => null,
			'dLastActivity' => null,
			'premium' => false,
			'statement' => Player::ACTIVE,
		];
	}

	protected static function getClass(): string
	{
		return Player::class;
	}
}
