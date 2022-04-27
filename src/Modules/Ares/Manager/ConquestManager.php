<?php

namespace App\Modules\Ares\Manager;

use App\Classes\Entity\EntityManager;
use App\Classes\Library\Utils;
use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\LiveReport;
use App\Modules\Ares\Model\Report;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Gaia\Event\PlaceOwnerChangeEvent;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Gaia\Model\Place;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ConquestManager
{
    public function __construct(
        protected CommanderManager $commanderManager,
        protected PlaceManager $placeManager,
        protected PlayerManager $playerManager,
        protected ColorManager $colorManager,
        protected OrbitalBaseManager $orbitalBaseManager,
        protected PlayerBonusManager $playerBonusManager,
        protected ReportManager $reportManager,
        protected EntityManager $entityManager,
        protected EventDispatcherInterface $eventDispatcher,
        protected NotificationManager $notificationManager,
        protected int $colonizationCost,
        protected int $conquestCost,
    ) {
    }

    public function getColonizationCost(Player $player, int $totalBases): int
    {
        return $this->processAttackCost($player, $this->colonizationCost, $totalBases);
    }

    public function getConquestCost(Player $player, int $totalBases): int
    {
        return $this->processAttackCost($player, $this->conquestCost, $totalBases);
    }

    private function processAttackCost(Player $player, int $cost, int $totalBases): int
    {
        $price = $cost * $totalBases;

        if (ColorResource::CARDAN == $player->rColor) {
            // bonus if the player is from Cardan
            $price -= round($price * ColorResource::BONUS_CARDAN_COLO / 100);
        }

        return $price;
    }

    public function conquer(Commander $commander): void
    {
        $place = $this->placeManager->get($commander->rDestinationPlace);
        $place->commanders = $this->commanderManager->getBaseCommanders($place->id);
        $placeBase = $this->orbitalBaseManager->get($place->id);
        $commanderPlace = $this->placeManager->get($commander->rBase);
        $commanderPlayer = $this->playerManager->get($commander->rPlayer);
        $commanderColor = $this->colorManager->get($commanderPlayer->rColor);
        $baseCommanders = $this->commanderManager->getBaseCommanders($place->getId());
        $playerBonus = $this->playerBonusManager->getBonusByPlayer($commanderPlayer);
        // conquete
        if (null !== $place->rPlayer) {
            $placePlayer = $this->playerManager->get($place->rPlayer);
            if (($place->playerColor != $commander->getPlayerColor() && $place->playerLevel > 3 && Color::ALLY != $commanderColor->colorLink[$place->playerColor]) || (0 == $place->playerColor)) {
                $tempCom = [];

                for ($i = 0; $i < count($place->commanders); ++$i) {
                    if ($place->commanders[$i]->line <= 1) {
                        $tempCom[] = $place->commanders[$i];
                    }
                }
                for ($i = 0; $i < count($place->commanders); ++$i) {
                    if ($place->commanders[$i]->line >= 2) {
                        $tempCom[] = $place->commanders[$i];
                    }
                }

                $place->commanders = $tempCom;

                $nbrBattle = 0;
                $reportIds = [];
                $reportArray = [];

                while ($nbrBattle < count($place->commanders)) {
                    if (Commander::AFFECTED == $place->commanders[$nbrBattle]->statement) {
                        LiveReport::$type = Commander::COLO;
                        LiveReport::$dFight = $commander->dArrival;

                        if (Color::ALLY == $commanderColor->colorLink[$place->playerColor] || Color::PEACE == $commanderColor->colorLink[$place->playerColor]) {
                            LiveReport::$isLegal = Report::ILLEGAL;
                        } else {
                            LiveReport::$isLegal = Report::LEGAL;
                        }

                        $this->commanderManager->startFight($place, $commander, $commanderPlayer, $place->commanders[$nbrBattle], $placePlayer, true);

                        $report = $this->commanderManager->createReport($place);
                        $reportArray[] = $report;
                        $reportIds[] = $report->id;
                        // PATCH DEGUEU POUR LES MUTLIS-COMBATS
                        $this->entityManager->clear($report);
                        $reports = $this->reportManager->getByAttackerAndPlace($commander->rPlayer, $place->id, $commander->dArrival);
                        foreach ($reports as $r) {
                            if ($r->id == $report->id) {
                                continue;
                            }
                            $r->statementAttacker = Report::DELETED;
                            $r->statementDefender = Report::DELETED;
                        }
                        $this->entityManager->flush(Report::class);
                        $this->entityManager->clear(Report::class);
                        // #######################################

                        // mettre à jour armyInBegin si prochain combat pour prochain rapport
                        for ($j = 0; $j < count($commander->armyAtEnd); ++$j) {
                            for ($i = 0; $i < 12; ++$i) {
                                $commander->armyInBegin[$j][$i] = $commander->armyAtEnd[$j][$i];
                            }
                        }
                        for ($j = 0; $j < count($place->commanders[$nbrBattle]->armyAtEnd); ++$j) {
                            for ($i = 0; $i < 12; ++$i) {
                                $place->commanders[$nbrBattle]->armyInBegin[$j][$i] = $place->commanders[$nbrBattle]->armyAtEnd[$j][$i];
                            }
                        }

                        ++$nbrBattle;
                        // mort du commandant
                        // arrêt des combats
                        if (Commander::DEAD == $commander->getStatement()) {
                            break;
                        }
                    } else {
                        ++$nbrBattle;
                    }
                }

                // victoire
                if (Commander::DEAD != $commander->getStatement()) {
                    if (0 == $nbrBattle) {
                        $this->placeManager->sendNotif($place, Place::CONQUERPLAYERWHITOUTBATTLESUCCESS, $commander, null);
                    } else {
                        $this->placeManager->sendNotifForConquest($place, Place::CONQUERPLAYERWHITBATTLESUCCESS, $commander, $reportIds);
                    }

                    // attribuer le joueur à la place
                    $place->commanders = [];
                    $place->playerColor = $commander->playerColor;
                    $place->rPlayer = $commander->rPlayer;

                    // changer l'appartenance de la base (et de la place)
                    $this->orbitalBaseManager->changeOwnerById($place->id, $placeBase, $commander->getRPlayer(), $baseCommanders);
                    $place->commanders[] = $commander;

                    $commander->rBase = $place->id;
                    $this->commanderManager->endTravel($commander, Commander::AFFECTED);
                    $commander->line = 2;

                    $this->eventDispatcher->dispatch(new PlaceOwnerChangeEvent($place), PlaceOwnerChangeEvent::NAME);

                    // PATCH DEGUEU POUR LES MUTLIS-COMBATS
                    $this->notificationManager->patchForMultiCombats($commander->rPlayer, $place->rPlayer, $commander->dArrival);
                // défaite
                } else {
                    for ($i = 0; $i < count($place->commanders); ++$i) {
                        if (Commander::DEAD == $place->commanders[$i]->statement) {
                            unset($place->commanders[$i]);
                            $place->commanders = array_merge($place->commanders);
                        }
                    }

                    $this->placeManager->sendNotifForConquest($place, Place::CONQUERPLAYERWHITBATTLEFAIL, $commander, $reportIds);
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

            // colonisation
        } else {
            // faire un combat
            LiveReport::$type = Commander::COLO;
            LiveReport::$dFight = $commander->dArrival;
            LiveReport::$isLegal = Report::LEGAL;

            $this->commanderManager->startFight($place, $commander, $commanderPlayer);

            // victoire
            if (Commander::DEAD !== $commander->getStatement()) {
                // attribuer le rPlayer à la Place !
                $place->rPlayer = $commander->rPlayer;
                $place->commanders[] = $commander;
                $place->playerColor = $commander->playerColor;
                $place->typeOfBase = 4;

                // créer une base
                $ob = new OrbitalBase();
                $ob->rPlace = $place->id;
                $ob->setRPlayer($commander->getRPlayer());
                $ob->setName('colonie');
                $ob->iSchool = 500;
                $ob->iAntiSpy = 500;
                $ob->resourcesStorage = 2000;
                $ob->uOrbitalBase = Utils::now();
                $ob->dCreation = Utils::now();
                $this->orbitalBaseManager->updatePoints($ob);

                $this->orbitalBaseManager->add($ob);

                // attibuer le commander à la place
                $commander->rBase = $place->id;
                $this->commanderManager->endTravel($commander, Commander::AFFECTED);
                $commander->line = 2;

                // création du rapport
                $report = $this->commanderManager->createReport($place);

                $place->danger = 0;

                $this->placeManager->sendNotif($place, Place::CONQUEREMPTYSSUCCESS, $commander, $report->id);

                $this->eventDispatcher->dispatch(new PlaceOwnerChangeEvent($place), PlaceOwnerChangeEvent::NAME);

            // défaite
            } else {
                // création du rapport
                $report = $this->commanderManager->createReport($place);

                // mise à jour du danger
                $percentage = (($report->pevAtEndD + 1) / ($report->pevInBeginD + 1)) * 100;
                $place->danger = round(($percentage * $place->danger) / 100);

                $this->placeManager->sendNotif($place, Place::CONQUEREMPTYFAIL, $commander);

                // enlever le commandant de la place
                foreach ($commanderPlace->commanders as $placeCommander) {
                    if ($placeCommander->getId() == $commander->getId()) {
                        unset($placeCommander);
                        $commanderPlace->commanders = array_merge($commanderPlace->commanders);
                    }
                }
            }
        }
        $this->entityManager->flush(Commander::class);
        $this->entityManager->flush($place);
    }
}
