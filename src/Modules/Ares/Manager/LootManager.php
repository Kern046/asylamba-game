<?php

namespace App\Modules\Ares\Manager;

use App\Classes\Entity\EntityManager;
use App\Modules\Ares\Domain\Event\Fleet\LootEvent;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\LiveReport;
use App\Modules\Ares\Model\Report;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Gaia\Model\Place;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Manager\PlayerManager;
use Psr\EventDispatcher\EventDispatcherInterface;

class LootManager
{
    public function __construct(
        protected EntityManager $entityManager,
        protected EventDispatcherInterface $eventDispatcher,
        protected CommanderManager $commanderManager,
        protected PlayerManager $playerManager,
        protected OrbitalBaseManager $orbitalBaseManager,
        protected PlaceManager $placeManager,
        protected ColorManager $colorManager,
        protected PlayerBonusManager $playerBonusManager,
    ) {
    }

    public function loot(Commander $commander): void
    {
        $place = $this->placeManager->get($commander->rDestinationPlace);
        $place->commanders = $this->commanderManager->getBaseCommanders($place->id);
        $placePlayer = $place->rPlayer ? $this->playerManager->get($place->rPlayer) : null;
        $placeBase = $this->orbitalBaseManager->get($place->id);
        $commanderPlace = $this->placeManager->get($commander->rBase);
        $commanderPlayer = $this->playerManager->get($commander->rPlayer);
        $commanderColor = $this->colorManager->get($commanderPlayer->rColor);
        $playerBonus = $this->playerBonusManager->getBonusByPlayer($commanderPlayer);
        LiveReport::$type = Commander::LOOT;
        LiveReport::$dFight = $commander->dArrival;

        // si la planète est vide
        if (null == $place->rPlayer) {
            LiveReport::$isLegal = Report::LEGAL;

            // planète vide : faire un combat
            $this->commanderManager->startFight($place, $commander, $commanderPlayer);

            // victoire
            if (Commander::DEAD != $commander->getStatement()) {
                // piller la planète
                $this->commanderManager->lootAnEmptyPlace($place, $commander, $playerBonus);
                // création du rapport de combat
                $report = $this->commanderManager->createReport($place);

                // réduction de la force de la planète
                $percentage = (($report->pevAtEndD + 1) / ($report->pevInBeginD + 1)) * 100;
                $place->danger = round(($percentage * $place->danger) / 100);

                $this->commanderManager->comeBack($place, $commander, $commanderPlace, $playerBonus);
                $this->placeManager->sendNotif($place, Place::LOOTEMPTYSSUCCESS, $commander, $report->id);
            } else {
                // si il est mort
                // enlever le commandant de la session
                for ($i = 0; $i < count($commanderPlace->commanders); ++$i) {
                    if ($commanderPlace->commanders[$i]->getId() == $commander->getId()) {
                        unset($commanderPlace->commanders[$i]);
                        $commanderPlace->commanders = array_merge($commanderPlace->commanders);
                    }
                }

                // création du rapport de combat
                $report = $this->commanderManager->createReport($place);
                $this->placeManager->sendNotif($place, Place::LOOTEMPTYFAIL, $commander, $report->id);

                // réduction de la force de la planète
                $percentage = (($report->pevAtEndD + 1) / ($report->pevInBeginD + 1)) * 100;
                $place->danger = round(($percentage * $place->danger) / 100);
            }
            // si il y a une base d'un joueur
        } else {
            if (Color::ALLY == $commanderColor->colorLink[$place->playerColor] || Color::PEACE == $commanderColor->colorLink[$place->playerColor]) {
                LiveReport::$isLegal = Report::ILLEGAL;
            } else {
                LiveReport::$isLegal = Report::LEGAL;
            }

            // planète à joueur : si $this->rColor != commandant->rColor
            // si il peut l'attaquer
            if (($place->playerColor != $commander->getPlayerColor() && $place->playerLevel > 1 && Color::ALLY != $commanderColor->colorLink[$place->playerColor]) || (0 == $place->playerColor)) {
                $dCommanders = [];
                foreach ($place->commanders as $dCommander) {
                    if (Commander::AFFECTED == $dCommander->statement && 1 == $dCommander->line) {
                        $dCommanders[] = $dCommander;
                    }
                }

                // il y a des commandants en défense : faire un combat avec un des commandants
                if (0 != count($dCommanders)) {
                    $aleaNbr = rand(0, count($dCommanders) - 1);
                    $this->commanderManager->startFight($place, $commander, $commanderPlayer, $dCommanders[$aleaNbr], $placePlayer, true);

                    // victoire
                    if (Commander::DEAD != $commander->getStatement()) {
                        // piller la planète
                        $this->commanderManager->lootAPlayerPlace($commander, $playerBonus, $placeBase);
                        $this->commanderManager->comeBack($place, $commander, $commanderPlace, $playerBonus);

                        // suppression des commandants
                        unset($place->commanders[$aleaNbr]);
                        $place->commanders = array_merge($place->commanders);

                        // création du rapport
                        $report = $this->commanderManager->createReport($place);

                        $this->placeManager->sendNotif($place, Place::LOOTPLAYERWHITBATTLESUCCESS, $commander, $report->id);

                    // défaite
                    } else {
                        // enlever le commandant de la session
                        for ($i = 0; $i < count($commanderPlace->commanders); ++$i) {
                            if ($commanderPlace->commanders[$i]->getId() == $commander->getId()) {
                                unset($commanderPlace->commanders[$i]);
                                $commanderPlace->commanders = array_merge($commanderPlace->commanders);
                            }
                        }

                        // création du rapport
                        $report = $this->commanderManager->createReport($place);

                        // mise à jour des flottes du commandant défenseur
                        for ($j = 0; $j < count($dCommanders[$aleaNbr]->armyAtEnd); ++$j) {
                            for ($i = 0; $i < 12; ++$i) {
                                $dCommanders[$aleaNbr]->armyInBegin[$j][$i] = $dCommanders[$aleaNbr]->armyAtEnd[$j][$i];
                            }
                        }

                        $this->placeManager->sendNotif($place, Place::LOOTPLAYERWHITBATTLEFAIL, $commander, $report->id);
                    }
                } else {
                    $this->commanderManager->lootAPlayerPlace($commander, $playerBonus, $placeBase);
                    $this->commanderManager->comeBack($place, $commander, $commanderPlace, $playerBonus);
                    $this->placeManager->sendNotif($place, Place::LOOTPLAYERWHITOUTBATTLESUCCESS, $commander);
                }
            } else {
                // si c'est la même couleur
                if ($place->rPlayer == $commander->rPlayer) {
                    // si c'est une de nos planètes
                    // on tente de se poser
                    $this->commanderManager->uChangeBase($commander);
                } else {
                    // si c'est une base alliée
                    // on repart
                    $this->commanderManager->comeBack($place, $commander, $commanderPlace, $playerBonus);
                    $this->placeManager->sendNotif($place, Place::CHANGELOST, $commander);
                }
            }
        }
        $this->eventDispatcher->dispatch(new LootEvent($commander, $placePlayer));

        $this->entityManager->flush();
    }
}
