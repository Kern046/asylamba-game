<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Infrastructure\DataFixtures;

use App\Modules\Demeter\Infrastructure\DataFixtures\FactionFixtures;
use App\Modules\Gaia\Infrastructure\DataFixtures\Factory\SectorFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SectorFixtures extends Fixture implements DependentFixtureInterface
{
	public function load(ObjectManager $manager): void
	{
		SectorFactory::createMany(4);
	}

	public function getDependencies(): array
	{
		return [
			FactionFixtures::class,
		];
	}
}
