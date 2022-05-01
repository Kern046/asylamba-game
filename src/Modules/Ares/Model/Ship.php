<?php

/**
 * Commander.
 *
 * @author Noé Zufferey
 * @copyright Expansion - le jeu
 *
 * @update 14.02.14
 */

namespace App\Modules\Ares\Model;

use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Zeus\Model\PlayerBonusId;

class Ship
{
	public $id = 0;
	public $nbrName = 0;
	public $name = '';
	public $codeName = '';
	public $life = 0;
	public $speed = 0;
	public $attack = [];
	public $defense = 0;
	public $nbrAttack = 0;
	public $pev = 0;
	public $isAttacker;

	public function __construct($nbrName, $isAttacker)
	{
		$this->nbrName = $nbrName;
		$this->isAttacker = $isAttacker;

		$this->name = ShipResource::getInfo($nbrName, 'name');
		$this->codeName = ShipResource::getInfo($nbrName, 'codeName');
		$this->life = ShipResource::getInfo($nbrName, 'life');
		$this->speed = ShipResource::getInfo($nbrName, 'speed');
		$this->attack = ShipResource::getInfo($nbrName, 'attack');
		$this->defense = ShipResource::getInfo($nbrName, 'defense');
		$this->pev = ShipResource::getInfo($nbrName, 'pev');

		$this->nbrAttack = count($this->attack);
	}

	public function setBonus($bonus)
	{
		switch (ShipResource::getInfo($nbrName, 'class')) {
			case 0:
				if (true == $isAttacker) {
					$this->speed += round($this->speed * $bonus->get(PlayerBonusId::FIGHTER_SPEED) / 100);
					for ($i = 0; $i < $this->nbrAttack; ++$i) {
						$this->attack[$i] += round($this->attack[$i] * $bonus->get(PlayerBonusId::FIGHTER_ATTACK) / 100);
					}
					$this->defense += round($this->defense * $bonus->get(PlayerBonusId::FIGHTER_DEFENSE) / 100);
				}
			break;

			case 1:
				if (true == $isAttacker) {
					$this->speed += round($this->speed * $bonus->get(PlayerBonusId::CORVETTE_SPEED) / 100);
					for ($i = 0; $i < $this->nbrAttack; ++$i) {
						$this->attack[$i] += round($this->attack[$i] * $bonus->get(PlayerBonusId::CORVETTE_ATTACK) / 100);
					}
					$this->defense += round($this->defense * $bonus->get(PlayerBonusId::CORVETTE_DEFENSE) / 100);
				}
			break;

			case 2:
				if (true == $isAttacker) {
					$this->speed += round($this->speed * $bonus->get(PlayerBonusId::FRIGATE_SPEED) / 100);
					for ($i = 0; $i < $this->nbrAttack; ++$i) {
						$this->attack[$i] += round($this->attack[$i] * $bonus->get(PlayerBonusId::FRIGATE_ATTACK) / 100);
					}
					$this->defense += round($this->defense * $bonus->get(PlayerBonusId::FRIGATE_DEFENSE) / 100);
				}
			break;

			case 3:
				if (true == $isAttacker) {
					$this->speed += round($this->speed * $bonus->get(PlayerBonusId::DESTROYER_SPEED) / 100);
					for ($i = 0; $i < $this->nbrAttack; ++$i) {
						$this->attack[$i] += round($this->attack[$i] * $bonus->get(PlayerBonusId::DESTROYER_ATTACK) / 100);
					}
					$this->defense += round($this->defense * $bonus->get(PlayerBonusId::DESTROYER_DEFENSE) / 100);
				}
			break;
		}
	}

	public function engage($enemySquadron)
	{
		for ($i = 0; $i < $this->nbrAttack; ++$i) {
			if (0 == $enemySquadron->getNbrShips()) {
				break;
			}
			$keyOfEnemyShip = $this->chooseEnemy($enemySquadron);
			if (1 == $this->avoidance($enemySquadron->getShip($keyOfEnemyShip))) {
				$this->attack($keyOfEnemyShip, $i, $enemySquadron);
			}
		}

		return $enemySquadron;
	}

	protected function chooseEnemy($enemySquadron)
	{
		$aleaNbr = rand(0, $enemySquadron->getNbrShips() - 1);

		return $aleaNbr;
	}

	protected function attack($key, $i, $enemySquadron)
	{
		$damages = ceil(log(($this->attack[$i] / $enemySquadron->getShip($key)->getDefense()) + 1) * 4 * $this->attack[$i]);
		$enemySquadron->getShip($key)->receiveDamages($damages, $enemySquadron, $key);
	}

	protected function avoidance($enemyShip)
	{
		$avoidance = rand(0, $enemyShip->getSpeed());
		if ($avoidance > 80) {
			return 0;
		} else {
			return 1;
		}
	}

	public function receiveDamages($damages, $squadron, $key)
	{
		$this->life -= $damages;
		if ($this->life <= 0) {
			$this->life = 0;
			$squadron->destructShip($key);
		}
	}

	public function affectId($id)
	{
		$this->id = $id + 1;
	}

	// -----------------Getters------------------------

	public function getId()
	{
		return $this->id;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getCodeName()
	{
		return $this->codeName;
	}

	public function getNbrName()
	{
		return $this->nbrName;
	}

	public function getLife()
	{
		return $this->life;
	}

	public function getSpeed()
	{
		return $this->speed;
	}

	public function getAttack()
	{
		return $this->attack;
	}

	public function getDefense()
	{
		return $this->defense;
	}

	public function getPev()
	{
		return $this->pev;
	}

	// --------------Setters--------------

	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}
}
