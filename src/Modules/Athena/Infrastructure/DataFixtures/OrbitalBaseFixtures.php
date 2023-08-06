<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\DataFixtures;

use App\Modules\Athena\Infrastructure\DataFixtures\Factory\OrbitalBaseFactory;
use App\Modules\Gaia\Infrastructure\DataFixtures\PlaceFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrbitalBaseFixtures extends Fixture implements DependentFixtureInterface
{
	public function load(ObjectManager $manager): void
	{
		OrbitalBaseFactory::createMany(10);
	}

	public function getDependencies(): array
	{
		return [
			PlaceFixtures::class,
		];
	}
}
