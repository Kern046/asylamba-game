<?php

namespace Tests\App\Modules\Ares\Model;

use App\Modules\Ares\Model\Ship;
use App\Modules\Ares\Model\Squadron;

class ShipTest extends \PHPUnit\Framework\TestCase
{
	public function testEntity()
	{
		$createdAt = '2017-05-01 21:20:30';
		$affectedAt = '2017-05-02 21:20:30';
		$arrivedAt = '2017-05-06 21:20:30';
		$updatedAt = '2017-05-06 20:20:30';

		$ship = new Ship(1, Ship::TYPE_MINOTAURE);
		$ship->id = 1;

		$this->assertEquals(1, $ship->id);
		$this->assertEquals('Destroyer', $ship->name);
		$this->assertEquals('Minotaure', $ship->codeName);
		$this->assertEquals(Ship::TYPE_MINOTAURE, $ship->shipNumber);
		$this->assertEquals(1200, $ship->life);
		$this->assertEquals(88, $ship->speed);
		$this->assertEquals([35, 35, 35, 35, 25, 10, 10], $ship->attack);
		$this->assertEquals(120, $ship->defense);
		$this->assertEquals(75, $ship->pev);
	}

	public function testFight()
	{
		/*$ship = new ship();
		$ship->engage($this->getSquadronMock());*/
	}

	public function testBonus()
	{
	}

	protected function chooseEnemyMock(Squadron $enemySquadron): int
	{
		return 0;
	}

	public function getSquadronMock(): array
	{
		return [
			'id' => 1,
			'data' => [
				2,
				0,
				17,
				0,
				0,
				0,
				0,
				1,
				0,
				0,
				0,
				0,
				'2017-05-16 20:00:00',
				'2017-05-16 20:00:00',
			],
	  	];
	}
}
