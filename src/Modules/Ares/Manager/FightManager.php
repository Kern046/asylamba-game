<?php

/**
 * Fight Controller.
 *
 * @author Noé Zufferey
 * @copyright Expansion - le jeu
 *
 * @update 14.02.14
 */

namespace App\Modules\Ares\Manager;

use App\Classes\Library\DataAnalysis;
use App\Classes\Library\Utils;
use App\Modules\Ares\Model\LiveReport;
use App\Modules\Athena\Resource\ShipResource;

class FightManager
{
	public function __construct(
		protected CommanderManager $commanderManager,
		protected int $gaiaId,
	) {
		self::$currentLine = 3;
	}

	private $isAFight = false;

	// ATTRIBUT STATIC DE LIGNE COURANTE

	private static $currentLine = 0;

	private static $AFighterBonus = [1, 1, 1, 1];
	private static $ACorvetteBonus = [1, 1, 1, 1];
	private static $AFrigateBonus = [1, 1, 1, 1];
	private static $ADestroyerBonus = [1, 1, 1, 1];

	private static $DFighterBonus = [1, 1, 1, 1];
	private static $DCorvetteBonus = [1, 1, 1, 1];
	private static $DFrigateBonus = [1, 1, 1, 1];
	private static $DDestroyerBonus = [1, 1, 1, 1];

	public function setFightControl($isAFight = false)
	{
		$this->isAFight = $isAFight;
	}

	// GETTER

	public static function getCurrentLine()
	{
		return self::$currentLine;
	}

	public static function getAFighterBonus($i)
	{
		return self::$AFighterBonus[$i];
	}

	public static function getACorvetteBonus($i)
	{
		return self::$ACorvetteBonus[$i];
	}

	public static function getAFrigateBonus($i)
	{
		return self::$AFrigateBonus[$i];
	}

	public static function getADestroyerBonus($i)
	{
		return self::$ADestroyerBonus[$i];
	}

	public static function getDFighterBonus($i)
	{
		return self::$DFighterBonus[$i];
	}

	public static function getDCorvetteBonus($i)
	{
		return self::$DCorvetteBonus[$i];
	}

	public static function getDFrigateBonus($i)
	{
		return self::$DFrigateBonus[$i];
	}

	public static function getDDestroyerBonus($i)
	{
		return self::$DDestroyerBonus[$i];
	}

	// SETTER

	public function setPlayerBonus($ABonus, $DBonus)
	{
		if (isset($ABonus['global'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$AFighterBonus[$i] += $ABonus['global'][$i];
				self::$ACorvetteBonus[$i] += $ABonus['global'][$i];
				self::$AFrigateBonus[$i] += $ABonus['global'][$i];
				self::$ADestroyerBonus[$i] += $ABonus['global'][$i];
			}
		}

		if (isset($ABonus['fighter'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$AFighterBonus[$i] += $ABonus['fighter'][$i];
			}
		}

		if (isset($ABonus['corvette'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$ACorvetteBonus[$i] += $ABonus['corvette'][$i];
			}
		}

		if (isset($ABonus['frigate'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$AFrigateBonus[$i] += $ABonus['frigate'][$i];
			}
		}

		if (isset($ABonus['destroyer'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$ADestroyerBonus[$i] += $ABonus['destroyer'][$i];
			}
		}

		if (isset($DBonus['global'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$DFighterBonus[$i] += $DBonus['global'][$i];
				self::$DCorvetteBonus[$i] += $DBonus['global'][$i];
				self::$DFrigateBonus[$i] += $DBonus['global'][$i];
				self::$DDestroyerBonus[$i] += $DBonus['global'][$i];
			}
		}

		if (isset($DBonus['fighter'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$DFighterBonus[$i] += $DBonus['fighter'][$i];
			}
		}

		if (isset($DBonus['corvette'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$DCorvetteBonus[$i] += $DBonus['corvette'][$i];
			}
		}

		if (isset($DBonus['frigate'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$DFrigateBonus[$i] += $DBonus['frigate'][$i];
			}
		}

		if (isset($DBonus['destroyer'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$DDestroyerBonus[$i] += $DBonus['destroyer'][$i];
			}
		}
	}

	public function setEnvironmentBonus($ABonus, $DBonus)
	{
		if (isset($ABonus['global'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$AFighterBonus[$i] += $ABonus['global'][$i];
				self::$ACorvetteBonus[$i] += $ABonus['global'][$i];
				self::$AFrigateBonus[$i] += $ABonus['global'][$i];
				self::$ADestroyerBonus[$i] += $ABonus['global'][$i];
			}
		}

		if (isset($ABonus['fighter'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$AFighterBonus[$i] += $ABonus['fighter'][$i];
			}
		}

		if (isset($ABonus['corvette'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$ACorvetteBonus[$i] += $ABonus['corvette'][$i];
			}
		}

		if (isset($ABonus['frigate'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$AFrigateBonus[$i] += $ABonus['frigate'][$i];
			}
		}

		if (isset($ABonus['destroyer'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$ADestroyerBonus[$i] += $ABonus['destroyer'][$i];
			}
		}

		if (isset($DBonus['global'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$DFighterBonus[$i] += $DBonus['global'][$i];
				self::$DCorvetteBonus[$i] += $DBonus['global'][$i];
				self::$DFrigateBonus[$i] += $DBonus['global'][$i];
				self::$DDestroyerBonus[$i] += $DBonus['global'][$i];
			}
		}

		if (isset($DBonus['fighter'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$DFighterBonus[$i] += $DBonus['fighter'][$i];
			}
		}

		if (isset($DBonus['corvette'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$DCorvetteBonus[$i] += $DBonus['corvette'][$i];
			}
		}

		if (isset($DBonus['frigate'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$DFrigateBonus[$i] += $DBonus['frigate'][$i];
			}
		}

		if (isset($DBonus['destroyer'])) {
			for ($i = 0; $i < 4; ++$i) {
				self::$DDestroyerBonus[$i] += $DBonus['destroyer'][$i];
			}
		}
	}

	/**
	 * DEMARE LE COMBAT ENTRE DEUX COMMANDANT
	 *		COMPTE L'ARMEE D
	 *		si 0 vaisseaux
	 *			A gagne
	 *		SINON COMBAT
	 *		COMPTE L'ARMEE A
	 *		si 0 vaisseaux
	 *			D gagne
	 *		SINON COMBAT.
	 */
	public function startFight($commanderA, $playerA, $commanderD, $playerD = null)
	{
		$commanderA->setIsAttacker(true);
		$commanderD->setIsAttacker(false);

		$commanderA->setPevInBegin();
		$commanderD->setPevInBegin();

		LiveReport::$rPlayerAttacker = $commanderA->rPlayer;
		LiveReport::$rPlayerDefender = $commanderD->rPlayer;

		LiveReport::$avatarA = $commanderA->avatar;
		LiveReport::$avatarD = $commanderD->avatar;
		LiveReport::$nameA = $commanderA->name;
		LiveReport::$nameD = $commanderD->name;
		LiveReport::$levelA = $commanderA->level;
		LiveReport::$levelD = $commanderD->level;
		LiveReport::$experienceA = $commanderA->experience;
		LiveReport::$experienceD = $commanderD->experience;
		LiveReport::$palmaresA = $commanderA->palmares;
		LiveReport::$palmaresD = $commanderD->palmares;

		$shipsA = $commanderA->getNbrShipByType();
		$shipsD = $commanderD->getNbrShipByType();
		$weight = 0;

		for ($i = 0; $i < count($shipsA); ++$i) {
			$weight += DataAnalysis::resourceToStdUnit(ShipResource::getInfo($i, 'resourcePrice') * $shipsA[$i]);
			$weight += DataAnalysis::resourceToStdUnit(ShipResource::getInfo($i, 'resourcePrice') * $shipsD[$i]);
		}

		LiveReport::$importance = $weight;

		$i = 0;
		foreach ($commanderA->armyInBegin as $s) {
			LiveReport::$squadrons[] = [0, $i, 0, 0, $commanderA->id, $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9], $s[10], $s[11]];
			++$i;
		}
		$i = 0;
		foreach ($commanderD->armyInBegin as $s) {
			LiveReport::$squadrons[] = [0, $i, 0, 0, $commanderD->id, $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9], $s[10], $s[11]];
			++$i;
		}

		while (1) {
			if (1000 == LiveReport::$round) {
				break;
			}
			$nbrShipsD = 0;
			$nbrShipsA = 0;

			foreach ($commanderA->getArmy() as $squadronA) {
				$nbrShipsA += $squadronA->getNbrShips();
			}
			if (0 == $nbrShipsA) {
				$this->commanderManager->resultOfFight($commanderD, true, $commanderA);
				$this->commanderManager->resultOfFight($commanderA, false, $commanderD);
				$commanderA->setStatement(3);
				$commanderD->setDDeath(Utils::now());
				LiveReport::$rPlayerWinner = $commanderD->rPlayer;

				if ($commanderD->rPlayer != $this->gaiaId) {
					$playerD->increaseVictory(1);
					$playerA->increaseDefeat(1);
				} else {
					$playerA->increaseDefeat(1);
				}

				break;
			} else {
				$commanderA = $this->commanderManager->engage($commanderD, $commanderA);
				++LiveReport::$halfround;
			}

			foreach ($commanderD->getArmy() as $squadronD) {
				$nbrShipsD += $squadronD->getNbrShips();
			}
			if (0 == $nbrShipsD) {
				$this->commanderManager->resultOfFight($commanderA, true, $commanderD);
				$this->commanderManager->resultOfFight($commanderD, false, $commanderA);
				$commanderD->setStatement(3);
				$commanderD->setDDeath(Utils::now());
				LiveReport::$rPlayerWinner = $commanderA->rPlayer;

				if ($commanderD->rPlayer != $this->gaiaId) {
					$playerA->increaseVictory(1);
					$playerD->increaseDefeat(1);
				} else {
					$playerA->increaseVictory(1);
				}

				break;
			} else {
				$commanderD = $this->commanderManager->engage($commanderA, $commanderD);
				++LiveReport::$halfround;
			}

			++LiveReport::$round;
			++self::$currentLine;
		}

		$i = 0;
		foreach ($commanderA->armyAtEnd as $s) {
			LiveReport::$squadrons[] = [0, $i, 0, -1, $commanderA->id, $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9], $s[10], $s[11]];
			++$i;
		}
		$i = 0;
		foreach ($commanderD->armyAtEnd as $s) {
			LiveReport::$squadrons[] = [0, $i, 0, -1, $commanderD->id, $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9], $s[10], $s[11]];
			++$i;
		}

		return [$commanderA, $commanderD];
	}
}
