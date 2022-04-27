<?php

/**
 * Vote Law.
 *
 * @author Noé Zufferey
 * @copyright Expansion - le jeu
 *
 * @update 29.09.14
 */

namespace App\Modules\Demeter\Model\Law;

class VoteLaw
{
    public $id = 0;
    public $rLaw = 0;
    public $rPlayer = 0;
    public $vote = 0;
    public $dVotation = '';

    public function getId()
    {
        return $this->id;
    }
}
