<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\DataFixtures;

use App\Modules\Demeter\Infrastructure\DataFixtures\FactionFixtures;
use App\Modules\Zeus\Infrastructure\DataFixtures\Factory\PlayerFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PlayerFixtures extends Fixture implements DependentFixtureInterface
{
	public function load(ObjectManager $manager): void
	{
		PlayerFactory::createMany(4);
	}

	public function getDependencies(): array
	{
		return [
			FactionFixtures::class,
		];
	}
}
