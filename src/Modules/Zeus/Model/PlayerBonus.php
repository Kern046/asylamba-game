<?php

/**
 * Player Bonus.
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @update 18.07.13
 */

namespace App\Modules\Zeus\Model;

class PlayerBonus
{
    // ATTRIBUTES
    public $rPlayer;
    public $synchronized = false;
    public $technology;
    public $bonus;
    public $playerColor;

    // CONSTANTS
    public const BONUS_QUANTITY = 28;
    // 28 bonus de technos
    public const GENERATOR_SPEED = 0;
    public const REFINERY_REFINING = 1;
    public const REFINERY_STORAGE = 2;
    public const DOCK1_SPEED = 3;
    public const DOCK2_SPEED = 4;
    public const TECHNOSPHERE_SPEED = 5;
    public const COMMERCIAL_INCOME = 6;
    public const GRAVIT_MODULE = 7;
    public const DOCK3_SPEED = 8;
    public const POPULATION_TAX = 9;
    public const COMMANDER_INVEST = 10;
    public const UNI_INVEST = 11;
    public const ANTISPY_INVEST = 12;
    public const SHIP_SPEED = 13; // vitesse de déplacement
    public const SHIP_CONTAINER = 14;
    public const BASE_QUANTITY = 15;
    public const FIGHTER_SPEED = 16;
    public const FIGHTER_ATTACK = 17;
    public const FIGHTER_DEFENSE = 18;
    public const CORVETTE_SPEED = 19;
    public const CORVETTE_ATTACK = 20;
    public const CORVETTE_DEFENSE = 21;
    public const FRIGATE_SPEED = 22;
    public const FRIGATE_ATTACK = 23;
    public const FRIGATE_DEFENSE = 24;
    public const DESTROYER_SPEED = 25;
    public const DESTROYER_ATTACK = 26;
    public const DESTROYER_DEFENSE = 27;
}
