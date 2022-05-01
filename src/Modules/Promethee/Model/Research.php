<?php

/**
 * Research.
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @update 19.04.13
 */

namespace App\Modules\Promethee\Model;

class Research
{
	// ATTRIBUTES
	public $rPlayer;
	public $mathLevel = 0;						// naturalTech
	public $physLevel = 0;
	public $chemLevel = 0;
	public $bioLevel = 0;	// bio == law		//lifeTech
	public $mediLevel = 0;  // medi == comm
	public $econoLevel = 0;						// socialTech
	public $psychoLevel = 0;
	public $networkLevel = 0;					// informaticTech
	public $algoLevel = 0;
	public $statLevel = 0;
	public $naturalTech = 0;
	public $lifeTech = 0;
	public $socialTech = 0;
	public $informaticTech = 0;
	public $naturalToPay;
	public $lifeToPay;
	public $socialToPay;
	public $informaticToPay;

	public const MATH = 0;
	public const PHYS = 1;
	public const CHEM = 2;
	public const LAW = 3;
	public const COMM = 4;
	public const ECONO = 5;
	public const PSYCHO = 6;
	public const NETWORK = 7;
	public const ALGO = 8;
	public const STAT = 9;

	public function getId()
	{
		return $this->rPlayer;
	}

	public function getLevel($id)
	{
		switch ($id) {
			case 0: return $this->mathLevel; break;
			case 1: return $this->physLevel; break;
			case 2: return $this->chemLevel; break;
			case 3: return $this->bioLevel; break;
			case 4: return $this->mediLevel; break;
			case 5: return $this->econoLevel; break;
			case 6: return $this->psychoLevel; break;
			case 7: return $this->networkLevel; break;
			case 8: return $this->algoLevel; break;
			case 9: return $this->statLevel; break;
			default: return false;
		}
	}
}
