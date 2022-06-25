<?php

namespace App\Tests\Modules\Ares\Application\Handler;

use App\Modules\Ares\Application\Handler\VirtualCommanderHandler;
use App\Modules\Gaia\Model\Place;
use App\Modules\Gaia\Model\Sector;
use App\Modules\Gaia\Model\System;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class VirtualCommanderHandlerTest extends TestCase
{
	/**
	 * @dataProvider provideData
	 */
	public function testCreateVirtualCommander(Place $place, int $expectedSquadronsCount, int $expectedPev): void
	{
		$handler = new VirtualCommanderHandler();

		$commander = $handler->createVirtualCommander($place);

		static::assertSame('rebelle', $commander->name);
		static::assertTrue($commander->isAffected());
		static::assertSame($expectedSquadronsCount, $commander->level);
		static::assertCount($expectedSquadronsCount, $commander->squadronsIds);
		static::assertCount($expectedSquadronsCount, $commander->army);
		static::assertSame($expectedPev, $commander->getPev());
	}

	public function provideData(): \Generator
	{
		yield [
			$this->getPlaceMock(
				danger: 0,
				maxDanger: 10,
				population: 147,
				position: 1,
				historyCoeff: 20,
				resourcesCoeff: 60,
			),
			3,
			18,
		];
		yield [
			$this->getPlaceMock(
				danger: 3,
				maxDanger: 10,
				population: 147,
				position: 1,
				historyCoeff: 20,
				resourcesCoeff: 60,
			),
			3,
			36,
		];
		yield [
			$this->getPlaceMock(
				danger: 0,
				maxDanger: 7,
				population: 100,
				position: 1,
				historyCoeff: 10,
				resourcesCoeff: 40,
			),
			3,
			18,
		];
		yield [
			$this->getPlaceMock(
				danger: 3,
				maxDanger: 7,
				population: 100,
				position: 3,
				historyCoeff: 70,
				resourcesCoeff: 10,
			),
			3,
			36,
		];
	}

	private function getPlaceMock(
		int $danger,
		int $maxDanger,
		int $population,
		int $position,
		int $historyCoeff,
		int $resourcesCoeff,
	): Place {
		return new Place(
			id: Uuid::v4(),
			player: null,
			base: null,
			system: new System(
				id: Uuid::v4(),
				sector: new Sector(
					id: Uuid::v4(),
					identifier: 1,
					faction: null,
					xPosition: 10,
					yPosition: 10,
					xBarycentric: 0,
					yBarycentric: 0,
					tax: 5,
					name: null,
					points: 5,
					population: 0,
					lifePlanet: 10,
					prime: 1,
				),
				faction: null,
				xPosition: 10,
				yPosition: 10,
				typeOfSystem: 0,
			),
			typeOfPlace: Place::TERRESTRIAL,
			position: $position,
			population: $population,
			coefResources: $resourcesCoeff,
			coefHistory: $historyCoeff,
			resources: 0,
			danger: $danger,
			maxDanger: $maxDanger,
			updatedAt: new \DateTimeImmutable(),
		);
	}
}
