<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DataFixtures\Story;

use App\Modules\Athena\Infrastructure\DataFixtures\Factory\OrbitalBaseFactory;
use App\Modules\Demeter\Infrastructure\DataFixtures\Factory\FactionFactory;
use App\Modules\Gaia\Infrastructure\DataFixtures\Factory\PlaceFactory;
use App\Modules\Gaia\Infrastructure\DataFixtures\Factory\SectorFactory;
use App\Modules\Gaia\Infrastructure\DataFixtures\Factory\SystemFactory;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Story;

class SmallMapStory extends Story
{
	public function build(): void
	{
		Factory::delayFlush(function () {
			FactionFactory::createMany(3);

			SectorFactory::createMany(3);

			SystemFactory::createMany(50);

			PlaceFactory::createMany(250);

			OrbitalBaseFactory::createMany(10);
		});
	}
}
