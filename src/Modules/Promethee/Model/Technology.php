<?php

/**
 * Technology.
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @update 04.06.13
 */

namespace App\Modules\Promethee\Model;

class Technology
{
    // ATTRIBUTES
    public $rPlayer;

    // technologies de débloquage (0 = bloqué, 1 = débloqué)
    public $comPlatUnblock = 0;
    public $dock2Unblock = 0;
    public $dock3Unblock = 0;
    public $recyclingUnblock = 0;
    public $spatioportUnblock = 0;

    public $ship0Unblock = 0;
    public $ship1Unblock = 0;
    public $ship2Unblock = 0;
    public $ship3Unblock = 0;
    public $ship4Unblock = 0;
    public $ship5Unblock = 0;
    public $ship6Unblock = 0;
    public $ship7Unblock = 0;
    public $ship8Unblock = 0;
    public $ship9Unblock = 0;
    public $ship10Unblock = 0;
    public $ship11Unblock = 0;

    public $colonization = 0;
    public $conquest = 0;

    // technologies à niveau
    public $generatorSpeed = 0;
    public $refineryRefining = 0;
    public $refineryStorage = 0;
    public $dock1Speed = 0;
    public $dock2Speed = 0;
    public $technosphereSpeed = 0;
    public $commercialIncomeUp = 0;
    public $gravitModuleUp = 0;
    public $dock3Speed = 0;

    public $populationTaxUp = 0;
    public $commanderInvestUp = 0;
    public $uniInvestUp = 0;
    public $antiSpyInvestUp = 0;

    public $spaceShipsSpeed = 0;
    public $spaceShipsContainer = 0; // soute

    public $baseQuantity = 0;

    public $fighterSpeed = 0;
    public $fighterAttack = 0;
    public $fighterDefense = 0;
    public $corvetteSpeed = 0;
    public $corvetteAttack = 0;
    public $corvetteDefense = 0;
    public $frigateSpeed = 0;
    public $frigateAttack = 0;
    public $frigateDefense = 0;
    public $destroyerSpeed = 0;
    public $destroyerAttack = 0;
    public $destroyerDefense = 0;

    // CONSTANTS
    public const COM_PLAT_UNBLOCK = 0;
    public const DOCK2_UNBLOCK = 1;
    public const DOCK3_UNBLOCK = 2;			// inactif
    public const RECYCLING_UNBLOCK = 3;
    public const SPATIOPORT_UNBLOCK = 4;
    public const SHIP0_UNBLOCK = 5;	// pegase
    public const SHIP1_UNBLOCK = 6;	// satyre
    public const SHIP2_UNBLOCK = 7;	// chimere
    public const SHIP3_UNBLOCK = 8;	// sirene
    public const SHIP4_UNBLOCK = 9;	// dryade
    public const SHIP5_UNBLOCK = 10;	// meduse
    public const SHIP6_UNBLOCK = 11;	// griffon
    public const SHIP7_UNBLOCK = 12;	// cyclope
    public const SHIP8_UNBLOCK = 13;	// minotaure
    public const SHIP9_UNBLOCK = 14;	// hydre
    public const SHIP10_UNBLOCK = 15;	// cerbere
    public const SHIP11_UNBLOCK = 16;	// phenix
    public const COLONIZATION = 17;
    public const CONQUEST = 18;
    public const GENERATOR_SPEED = 19;			// ok
    public const REFINERY_REFINING = 20;		// ok
    public const REFINERY_STORAGE = 21;		// ok
    public const DOCK1_SPEED = 22;				// ok
    public const DOCK2_SPEED = 23;				// ok
    public const TECHNOSPHERE_SPEED = 24;		// ok
    public const COMMERCIAL_INCOME = 25;		// ok
    public const GRAVIT_MODULE = 26;			// inactif
    public const DOCK3_SPEED = 27;				// inactif
    public const POPULATION_TAX = 28;			// ok
    public const COMMANDER_INVEST = 29;		// ok
    public const UNI_INVEST = 30;				// ok
    public const ANTISPY_INVEST = 31;
    public const SPACESHIPS_SPEED = 32;
    public const SPACESHIPS_CONTAINER = 33;
    public const BASE_QUANTITY = 34;
    public const FIGHTER_SPEED = 35;
    public const FIGHTER_ATTACK = 36;
    public const FIGHTER_DEFENSE = 37;
    public const CORVETTE_SPEED = 38;
    public const CORVETTE_ATTACK = 39;
    public const CORVETTE_DEFENSE = 40;
    public const FRIGATE_SPEED = 41;
    public const FRIGATE_ATTACK = 42;
    public const FRIGATE_DEFENSE = 43;
    public const DESTROYER_SPEED = 44;
    public const DESTROYER_ATTACK = 45;
    public const DESTROYER_DEFENSE = 46;

    public const QUANTITY = 47;

    public const COEF_POINTS = 1;
    public const COEF_TIME = 1;

    public function getTechnology($id)
    {
        switch ($id) {
            case 0: return $this->comPlatUnblock; break;
            case 1: return $this->dock2Unblock; break;
            case 2: return $this->dock3Unblock; break;
            case 3: return $this->recyclingUnblock; break;
            case 4: return $this->spatioportUnblock; break;
            case 5: return $this->ship0Unblock; break;
            case 6: return $this->ship1Unblock; break;
            case 7: return $this->ship2Unblock; break;
            case 8: return $this->ship3Unblock; break;
            case 9: return $this->ship4Unblock; break;
            case 10: return $this->ship5Unblock; break;
            case 11: return $this->ship6Unblock; break;
            case 12: return $this->ship7Unblock; break;
            case 13: return $this->ship8Unblock; break;
            case 14: return $this->ship9Unblock; break;
            case 15: return $this->ship10Unblock; break;
            case 16: return $this->ship11Unblock; break;
            case 17: return $this->colonization; break;
            case 18: return $this->conquest; break;
            case 19: return $this->generatorSpeed; break;
            case 20: return $this->refineryRefining; break;
            case 21: return $this->refineryStorage; break;
            case 22: return $this->dock1Speed; break;
            case 23: return $this->dock2Speed; break;
            case 24: return $this->technosphereSpeed; break;
            case 25: return $this->commercialIncomeUp; break;
            case 26: return $this->gravitModuleUp; break;
            case 27: return $this->dock3Speed; break;
            case 28: return $this->populationTaxUp; break;
            case 29: return $this->commanderInvestUp; break;
            case 30: return $this->uniInvestUp; break;
            case 31: return $this->antiSpyInvestUp; break;
            case 32: return $this->spaceShipsSpeed; break;
            case 33: return $this->spaceShipsContainer; break;
            case 34: return $this->baseQuantity; break;
            case 35: return $this->fighterSpeed; break;
            case 36: return $this->fighterAttack; break;
            case 37: return $this->fighterDefense; break;
            case 38: return $this->corvetteSpeed; break;
            case 39: return $this->corvetteAttack; break;
            case 40: return $this->corvetteDefense; break;
            case 41: return $this->frigateSpeed; break;
            case 42: return $this->frigateAttack; break;
            case 43: return $this->frigateDefense; break;
            case 44: return $this->destroyerSpeed; break;
            case 45: return $this->destroyerAttack; break;
            case 46: return $this->destroyerDefense; break;
            default: return false;
        }
    }

    public function setTechnology($id, $value)
    { // ajouter une entrée bdd ou modifier ligne !!!
        switch ($id) {
            case 0: $this->comPlatUnblock = $value; break;
            case 1: $this->dock2Unblock = $value; break;
            case 2: $this->dock3Unblock = $value; break;
            case 3: $this->recyclingUnblock = $value; break;
            case 4: $this->spatioportUnblock = $value; break;
            case 5: $this->ship0Unblock = $value; break;
            case 6: $this->ship1Unblock = $value; break;
            case 7: $this->ship2Unblock = $value; break;
            case 8: $this->ship3Unblock = $value; break;
            case 9: $this->ship4Unblock = $value; break;
            case 10: $this->ship5Unblock = $value; break;
            case 11: $this->ship6Unblock = $value; break;
            case 12: $this->ship7Unblock = $value; break;
            case 13: $this->ship8Unblock = $value; break;
            case 14: $this->ship9Unblock = $value; break;
            case 15: $this->ship10Unblock = $value; break;
            case 16: $this->ship11Unblock = $value; break;
            case 17: $this->colonization = $value; break;
            case 18: $this->conquest = $value; break;
            case 19: $this->generatorSpeed = $value; break;
            case 20: $this->refineryRefining = $value; break;
            case 21: $this->refineryStorage = $value; break;
            case 22: $this->dock1Speed = $value; break;
            case 23: $this->dock2Speed = $value; break;
            case 24: $this->technosphereSpeed = $value; break;
            case 25: $this->commercialIncomeUp = $value; break;
            case 26: $this->gravitModuleUp = $value; break;
            case 27: $this->dock3Speed = $value; break;
            case 28: $this->populationTaxUp = $value; break;
            case 29: $this->commanderInvestUp = $value; break;
            case 30: $this->uniInvestUp = $value; break;
            case 31: $this->antiSpyInvestUp = $value; break;
            case 32: $this->spaceShipsSpeed = $value; break;
            case 33: $this->spaceShipsContainer = $value; break;
            case 34: $this->baseQuantity = $value; break;
            case 35: $this->fighterSpeed = $value; break;
            case 36: $this->fighterAttack = $value; break;
            case 37: $this->fighterDefense = $value; break;
            case 38: $this->corvetteSpeed = $value; break;
            case 39: $this->corvetteAttack = $value; break;
            case 40: $this->corvetteDefense = $value; break;
            case 41: $this->frigateSpeed = $value; break;
            case 42: $this->frigateAttack = $value; break;
            case 43: $this->frigateDefense = $value; break;
            case 44: $this->destroyerSpeed = $value; break;
            case 45: $this->destroyerAttack = $value; break;
            case 46: $this->destroyerDefense = $value; break;
            default: return false;
        }
    }
}
