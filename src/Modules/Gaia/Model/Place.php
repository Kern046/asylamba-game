<?php
/**
 * Place.
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @update 21.04.13
*/

namespace App\Modules\Gaia\Model;

class Place
{
	// CONSTANTS
	public const TYP_EMPTY = 0;
	public const TYP_MS1 = 1;
	public const TYP_MS2 = 2;
	public const TYP_MS3 = 3;
	public const TYP_ORBITALBASE = 4;

	public const COEFFMAXRESOURCE = 600;
	public const COEFFRESOURCE = 2;
	public const REPOPDANGER = 2;
	public const COEFFPOPRESOURCE = 50;
	public const COEFFDANGER = 5;

	// typeOfPlace
	public const TERRESTRIAL = 1;
	public const EMPTYZONE = 6; // zone vide

	// CONST PNJ COMMANDER
	public const LEVELMAXVCOMMANDER = 20;
	public const POPMAX = 250;
	public const DANGERMAX = 100;

	// CONST RESULT BATTLE
	public const CHANGESUCCESS = 10;
	public const CHANGEFAIL = 11;
	public const CHANGELOST = 12;

	public const LOOTEMPTYSSUCCESS = 20;
	public const LOOTEMPTYFAIL = 21;
	public const LOOTPLAYERWHITBATTLESUCCESS = 22;
	public const LOOTPLAYERWHITBATTLEFAIL = 23;
	public const LOOTPLAYERWHITOUTBATTLESUCCESS = 24;
	public const LOOTLOST = 27;

	public const CONQUEREMPTYSSUCCESS = 30;
	public const CONQUEREMPTYFAIL = 31;
	public const CONQUERPLAYERWHITBATTLESUCCESS = 32;
	public const CONQUERPLAYERWHITBATTLEFAIL = 33;
	public const CONQUERPLAYERWHITOUTBATTLESUCCESS = 34;
	public const CONQUERLOST = 37;

	public const COMEBACK = 40;

	// constante de danger
	public const DNG_CASUAL = 10;
	public const DNG_EASY = 20;
	public const DNG_MEDIUM = 50;
	public const DNG_HARD = 75;
	public const DNG_VERY_HARD = 100;

	// PLACE
	public $id = 0;
	public $rPlayer = null;
	public $rSystem = 0;
	public $typeOfPlace = 0;
	public $position = 0;
	public $population = 0;
	public $coefResources = 0;
	public $coefHistory = 0;
	public $resources = 0; 						// de la place si $typeOfBase = 0, sinon de la base
	public $danger = 0;							// danger actuel de la place (force des flottes rebelles)
	public $maxDanger = 0;						// danger max de la place (force des flottes rebelles)
	public $uPlace = '';

	// SYSTEM
	public $rSector = 0;
	public $xSystem = 0;
	public $ySystem = 0;
	public $typeOfSystem = 0;

	// SECTOR
	public $tax = 0;
	public $sectorColor = 0;

	// PLAYER
	public $playerColor = 0;
	public $playerName = '';
	public $playerAvatar = '';
	public $playerStatus = 0;
	public $playerLevel = 0;

	// BASE
	public $typeOfBase = 0;
	public $typeOfOrbitalBase;
	public $baseName = '';
	public $points = '';

	// OB
	public $levelCommercialPlateforme = 0;
	public $levelSpatioport = 0;
	public $antiSpyInvest = 0;

	// COMMANDER
	public $commanders = [];

	// uMode
	public $uMode = true;

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setId($v)
	{
		$this->id = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setRPlayer($v)
	{
		$this->rPlayer = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getRPlayer()
	{
		return $this->rPlayer;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setRSystem($v)
	{
		$this->rSystem = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getRSystem()
	{
		return $this->rSystem;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setTypeOfPlace($v)
	{
		$this->typeOfPlace = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getTypeOfPlace()
	{
		return $this->typeOfPlace;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setPosition($v)
	{
		$this->position = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPosition()
	{
		return $this->position;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setPopulation($v)
	{
		$this->population = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPopulation()
	{
		return $this->population;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setCoefResources($v)
	{
		$this->coefResources = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCoefResources()
	{
		return $this->coefResources;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setCoefHistory($v)
	{
		$this->coefHistory = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCoefHistory()
	{
		return $this->coefHistory;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setResources($v)
	{
		$this->resources = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getResources()
	{
		return $this->resources;
	}

	/**
	 * @param int $danger
	 *
	 * @return $this
	 */
	public function setDanger($danger)
	{
		$this->danger = $danger;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getDanger()
	{
		return $this->danger;
	}

	/**
	 * @param int $maxDanger
	 *
	 * @return $this
	 */
	public function setMaxDanger($maxDanger)
	{
		$this->maxDanger = $maxDanger;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getMaxDanger()
	{
		return $this->maxDanger;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setRSector($v)
	{
		$this->rSector = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getRSector()
	{
		return $this->rSector;
	}

	/**
	 * @param float $v
	 *
	 * @return $this
	 */
	public function setXSystem($v)
	{
		$this->xSystem = $v;

		return $this;
	}

	/**
	 * @return float
	 */
	public function getXSystem()
	{
		return $this->xSystem;
	}

	/**
	 * @param float $v
	 *
	 * @return $this
	 */
	public function setYSystem($v)
	{
		$this->ySystem = $v;

		return $this;
	}

	/**
	 * @return float
	 */
	public function getYSystem()
	{
		return $this->ySystem;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setTypeOfSystem($v)
	{
		$this->typeOfSystem = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getTypeOfSystem()
	{
		return $this->typeOfSystem;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setTax($v)
	{
		$this->tax = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getTax()
	{
		return $this->tax;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setSectorColor($v)
	{
		$this->sectorColor = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getSectorColor()
	{
		return $this->sectorColor;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setPlayerColor($v)
	{
		$this->playerColor = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPlayerColor()
	{
		return $this->playerColor;
	}

	/**
	 * @param string $v
	 *
	 * @return $this
	 */
	public function setPlayerName($v)
	{
		$this->playerName = $v;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPlayerName()
	{
		return $this->playerName;
	}

	/**
	 * @param string $v
	 *
	 * @return $this
	 */
	public function setPlayerAvatar($v)
	{
		$this->playerAvatar = $v;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPlayerAvatar()
	{
		return $this->playerAvatar;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setPlayerStatus($v)
	{
		$this->playerStatus = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPlayerStatus()
	{
		return $this->playerStatus;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setTypeOfBase($v)
	{
		$this->typeOfBase = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getTypeOfBase()
	{
		return $this->typeOfBase;
	}

	/**
	 * @param string $v
	 *
	 * @return $this
	 */
	public function setBaseName($v)
	{
		$this->baseName = $v;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getBaseName()
	{
		return $this->baseName;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setPoints($v)
	{
		$this->points = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPoints()
	{
		return $this->points;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setLevelCommercialPlateforme($v)
	{
		$this->levelCommercialPlateforme = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLevelCommercialPlateforme()
	{
		return $this->levelCommercialPlateforme;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setLevelSpatioport($v)
	{
		$this->levelSpatioport = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLevelSpatioport()
	{
		return $this->levelSpatioport;
	}

	/**
	 * @param int $v
	 *
	 * @return $this
	 */
	public function setAntiSpyInvest($v)
	{
		$this->antiSpyInvest = $v;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getAntiSpyInvest()
	{
		return $this->antiSpyInvest;
	}
}
