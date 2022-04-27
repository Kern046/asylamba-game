<?php

namespace App\Modules\Gaia\EventListener;

use App\Classes\Entity\EntityManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Gaia\Event\PlaceOwnerChangeEvent;
use App\Modules\Gaia\Manager\SystemManager;
use App\Modules\Zeus\Manager\PlayerManager;

class SystemListener
{
    public function __construct(
        protected SystemManager $systemManager,
        protected OrbitalBaseManager $orbitalBaseManager,
        protected PlayerManager $playerManager,
        protected EntityManager $entityManager,
        protected array $scores
    ) {
    }

    public function onPlaceOwnerChange(PlaceOwnerChangeEvent $event)
    {
        $system = $this->systemManager->get($event->getPlace()->rSystem);
        $bases = $this->orbitalBaseManager->getSystemBases($system);
        // Initialize the value in case no base is available (after leaving the last one)
        $scores[$system->rColor] = 0;

        foreach ($bases as $base) {
            $player = $this->playerManager->get($base->rPlayer);

            $scores[$player->rColor] =
                (!empty($scores[$player->rColor]))
                ? $scores[$player->rColor] + $this->scores[$base->typeOfBase]
                : $this->scores[$base->typeOfBase]
            ;
        }
        arsort($scores);
        reset($scores);
        $newColor = key($scores);
        // NPC faction has no points
        $scores[0] = 0;
        if ($scores[$newColor] > 0 && $system->rColor !== $newColor && $scores[$newColor] > $scores[$system->rColor]) {
            $system->rColor = $newColor;
        } elseif (0 === $scores[$newColor]) {
            $system->rColor = 0;
        }
        $this->systemManager->changeOwnership($system);
    }
}
