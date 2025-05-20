<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\DataFixtures;

use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FactionFixtures extends Fixture
{
	public function load(ObjectManager $manager): void
	{
		FactionFactory::createMany(4);
	}
}
