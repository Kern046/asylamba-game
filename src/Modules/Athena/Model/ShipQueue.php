<?php

/**
 * ShipQueue.
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @update 10.02.14
 */

namespace App\Modules\Athena\Model;

use App\Shared\Domain\Model\QueueableInterface;

class ShipQueue implements QueueableInterface
{
    public int|null $id = null;
    public int $rOrbitalBase;
    public int $dockType = 0;
    public int $shipNumber = 0;
    public int $quantity = 1;
    public $dStart;
    public $dEnd;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getEndDate(): string
    {
        return $this->dEnd;
    }

    public function getResourceIdentifier(): int
    {
        return $this->shipNumber;
    }
}
