<?php

/**
 * Player.
 *
 * @author Gil Clavien
 * @copyright Expansion - le jeu
 *
 * @update 20.05.13
 */

namespace App\Modules\Zeus\Model;

class Player
{
	public int|null $id = 0;
	public string|null $bind = null;
	public int $rColor = 0;
	public int|null $rGodfather = null;
	public $name = '';
	public int $sex = 0;
	public $description = '';
	public $avatar = '';
	public int $status = 1;
	public int $credit = 0;
	public $uPlayer = '';
	public int $experience = 0;
	public int $factionPoint = 0;
	public int $level = 0;
	public int $victory = 0;
	public int $defeat = 0;
	public int $stepTutorial = 1;
	public int $stepDone = 0;
	public int $iUniversity = 5000;
	public int $partNaturalSciences = 25;
	public int $partLifeSciences = 25;
	public int $partSocialPoliticalSciences = 25;
	public int $partInformaticEngineering = 25;
	public $dInscription = '';
	public $dLastConnection = '';
	public $dLastActivity = '';
	public $premium = 0; 	// 0 = publicité, 1 = pas de publicité
	public $statement = 0;

	public bool $synchronized = false;

	public const ACTIVE = 1;
	public const INACTIVE = 2;
	public const HOLIDAY = 3;
	public const BANNED = 4;
	public const DELETED = 5;
	public const DEAD = 6;

	public const STANDARD = 1;
	public const PARLIAMENT = 2;
	public const TREASURER = 3;
	public const WARLORD = 4;
	public const MINISTER = 5;
	public const CHIEF = 6;

	public function getId()
	{
		return $this->id;
	}

	public function getBind()
	{
		return $this->bind;
	}

	public function getRColor()
	{
		return $this->rColor;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getAvatar()
	{
		return $this->avatar;
	}

	public function getStatus()
	{
		return $this->status;
	}

	public function getCredit()
	{
		return $this->credit;
	}

	public function getExperience()
	{
		return $this->experience;
	}

	public function getLevel()
	{
		return $this->level;
	}

	public function getVictory()
	{
		return $this->victory;
	}

	public function getDefeat()
	{
		return $this->defeat;
	}

	public function getStepTutorial()
	{
		return $this->stepTutorial;
	}

	public function getDInscription()
	{
		return $this->dInscription;
	}

	public function getDLastConnection()
	{
		return $this->dLastConnection;
	}

	public function getDLastActivity()
	{
		return $this->dLastActivity;
	}

	public function getPremium()
	{
		return $this->premium;
	}

	public function getStatement()
	{
		return $this->statement;
	}

	public function isSynchronized(): bool
	{
		return $this->synchronized;
	}

	public function isRuler(): bool
	{
		return self::CHIEF === $this->getStatus();
	}

	public function isSenator(): bool
	{
		return self::PARLIAMENT === $this->getStatus();
	}

	public function isGovernmentMember(): bool
	{
		return in_array($this->getStatus(), [self::CHIEF, self::WARLORD, self::TREASURER, self::MINISTER]);
	}

	public function isTreasurer(): bool
	{
		return self::TREASURER === $this->getStatus();
	}

	public function isParliamentMember(): bool
	{
		return $this->isSenator() || $this->isGovernmentMember();
	}

	public function isPeopleMember(): bool
	{
		return self::STANDARD === $this->getStatus();
	}

	public function setId($v)
	{
		$this->id = $v;

		return $this;
	}

	public function setBind($v)
	{
		$this->bind = $v;

		return $this;
	}

	public function setRColor($v)
	{
		$this->rColor = $v;

		return $this;
	}

	public function setName($v)
	{
		$this->name = $v;

		return $this;
	}

	public function setAvatar($v)
	{
		$this->avatar = $v;

		return $this;
	}

	public function setStatus($v)
	{
		$this->status = $v;

		return $this;
	}

	public function setCredit($v)
	{
		$this->credit = $v;

		return $this;
	}

	public function setExperience($v)
	{
		$this->experience = $v;

		return $this;
	}

	public function setLevel($v)
	{
		$this->level = $v;

		return $this;
	}

	public function setVictory($v)
	{
		$this->victory = $v;

		return $this;
	}

	public function setDefeat($v)
	{
		$this->defeat = $v;

		return $this;
	}

	public function setStepTutorial($v)
	{
		$this->stepTutorial = $v;

		return $this;
	}

	public function setDInscription($v)
	{
		$this->dInscription = $v;

		return $this;
	}

	public function setDLastConnection($v)
	{
		$this->dLastConnection = $v;

		return $this;
	}

	public function setDLastActivity($v)
	{
		$this->dLastActivity = $v;

		return $this;
	}

	public function setPremium($v)
	{
		$this->premium = $v;

		return $this;
	}

	public function setStatement($v)
	{
		$this->statement = $v;

		return $this;
	}

	public function setFactionPoints($factionPoints)
	{
		$this->factionPoints = $factionPoints;

		return $this;
	}

	public function increaseVictory($i)
	{
		$this->victory += $i;

		return $this;
	}

	public function increaseDefeat($i)
	{
		$this->defeat += $i;

		return $this;
	}
}
