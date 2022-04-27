<?php

/**
 * PlayerRanking.
 *
 * @author Jacky Casas
 * @copyright Asylamba
 *
 * @update 04.06.14
 */

namespace App\Modules\Atlas\Model;

use App\Modules\Zeus\Model\Player;

class PlayerRanking
{
    // set number of player before you (remove 1) in rank view
    public const PREV = 4;
    // set number of player after you in rank view
    public const NEXT = 8;
    // PREV + NEXT
    public const STEP = 12;
    // set number of player on ajax load page
    public const PAGE = 10;

    // attributes
    public $id;
    public $rRanking;
    public $rPlayer;

    /** @var Player * */
    protected $player;

    public $general;			// pts des bases + flottes + commandants
    public $generalPosition;
    public $generalVariation;

    public $experience; 		// experience
    public $experiencePosition;
    public $experienceVariation;

    public $butcher;			// destroyedPEV - lostPEV
    public $butcherDestroyedPEV;
    public $butcherLostPEV;
    public $butcherPosition;
    public $butcherVariation;

    public $trader;				// revenu total des routes
    public $traderPosition;
    public $traderVariation;

    public $fight; 				// victoires - défaites
    public $victories;
    public $defeat;
    public $fightPosition;
    public $fightVariation;

    public $armies;				// nb de pev total flotte + hangar
    public $armiesPosition;
    public $armiesVariation;

    public $resources; 			// production de ressources par relève (on peut ajouter les recyclages p-e)
    public $resourcesPosition;
    public $resourcesVariation;

    // additional attributes
    public $color;
    public $name;
    public $avatar;
    public $status;

    /**
     * @param int $id
     *
     * @return PlayerRanking
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return PlayerRanking
     */
    public function setPlayer(Player $player)
    {
        $this->player = $player;

        return $this;
    }

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @param int $general
     *
     * @return PlayerRanking
     */
    public function setGeneral($general)
    {
        $this->general = $general;

        return $this;
    }

    /**
     * @return int
     */
    public function getGeneral()
    {
        return $this->general;
    }
}
