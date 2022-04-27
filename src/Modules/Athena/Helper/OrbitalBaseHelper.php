<?php

namespace App\Modules\Athena\Helper;

use App\Classes\Library\Format;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Zeus\Model\PlayerBonus;
use Symfony\Component\HttpFoundation\RequestStack;

class OrbitalBaseHelper
{
    public function __construct(
        protected TechnologyHelper $technologyHelper,
        protected RequestStack $requestStack,
    ) {
    }

    public function isABuilding(int $building): bool
    {
        return \in_array($building, OrbitalBaseResource::$orbitalBaseBuildings);
    }

    public function isAShipFromDock1(int $ship): bool
    {
        return \in_array($ship, OrbitalBaseResource::$dock1Ships);
    }

    public function isAShipFromDock2(int $ship): bool
    {
        return \in_array($ship, OrbitalBaseResource::$dock2Ships);
    }

    public function isAShipFromDock3(int $ship): bool
    {
        return \in_array($ship, OrbitalBaseResource::$dock3Ships);
    }

    public function fleetQuantity(int $typeOfBase): int
    {
        return match ($typeOfBase) {
            OrbitalBase::TYP_NEUTRAL, OrbitalBase::TYP_COMMERCIAL => 2,
            OrbitalBase::TYP_MILITARY, OrbitalBase::TYP_CAPITAL => 5,
            default => 0,
        };
    }

    public function getStoragePercent(OrbitalBase $orbitalBase): float
    {
        $storageSpace = $this->getBuildingInfo(OrbitalBaseResource::STORAGE, 'level', $orbitalBase->getLevelStorage(), 'storageSpace');
        $storageBonus = $this->requestStack->getSession()->get('playerBonus')->get(PlayerBonus::REFINERY_STORAGE);
        if ($storageBonus > 0) {
            $storageSpace += ($storageSpace * $storageBonus / 100);
        }

        return Format::numberFormat($orbitalBase->getResourcesStorage() / $storageSpace * 100);
    }

    // @TODO Check for the need of this method ??
    public function getInfo($buildingNumber, $info, $level = 0, $sup = 'default')
    {
        return $this->getBuildingInfo($buildingNumber, $info, $level, $sup);
    }

    // @TODO Separate building logic from orbital-ase logic in a dedicated helper (for now)
    public function getBuildingInfo($buildingNumber, $info, $level = 0, $sup = 'default')
    {
        if ($this->isABuilding($buildingNumber)) {
            if (\in_array($info, ['name', 'column', 'frenchName', 'imageLink', 'description'])) {
                return OrbitalBaseResource::$building[$buildingNumber][$info];
            } elseif ('techno' == $info) {
                if (\in_array($buildingNumber, [3, 4, 6, 8, 9])) {
                    return OrbitalBaseResource::$building[$buildingNumber][$info];
                } else {
                    return -1;
                }
            } elseif ('maxLevel' == $info) {
                // $level is the type of the base
                return OrbitalBaseResource::$building[$buildingNumber][$info][$level];
            } elseif ('level' == $info) {
                if ($level <= 0 or $level > count(OrbitalBaseResource::$building[$buildingNumber]['level'])) {
                    return false;
                }
                if ('time' == $sup) {
                    return OrbitalBaseResource::$building[$buildingNumber][$info][$level - 1][0];
                } elseif ('resourcePrice' == $sup) {
                    return OrbitalBaseResource::$building[$buildingNumber][$info][$level - 1][1];
                } elseif ('points' == $sup) {
                    return OrbitalBaseResource::$building[$buildingNumber][$info][$level - 1][2];
                } else {
                    if ('nbQueues' == $sup) {
                        if (0 == $buildingNumber or 2 == $buildingNumber or 3 == $buildingNumber or 5 == $buildingNumber) {
                            return OrbitalBaseResource::$building[$buildingNumber][$info][$level - 1][3];
                        }
                    } elseif ('storageSpace' == $sup) {
                        if (7 == $buildingNumber) {
                            return OrbitalBaseResource::$building[$buildingNumber][$info][$level - 1][3];
                        } elseif (2 == $buildingNumber or 3 == $buildingNumber) {
                            return OrbitalBaseResource::$building[$buildingNumber][$info][$level - 1][4];
                        }
                    } elseif ('refiningCoefficient' == $sup and 1 == $buildingNumber) {
                        return OrbitalBaseResource::$building[$buildingNumber][$info][$level - 1][3];
                    } elseif ('releasedShip' == $sup and (2 == $buildingNumber or 3 == $buildingNumber)) {
                        return OrbitalBaseResource::$building[$buildingNumber][$info][$level - 1][5];
                    } elseif ('releasedShip' == $sup and 4 == $buildingNumber) {
                        return OrbitalBaseResource::$building[$buildingNumber][$info][$level - 1][4];
                    } elseif ('nbCommercialShip' == $sup and 6 == $buildingNumber) {
                        return OrbitalBaseResource::$building[$buildingNumber][$info][$level - 1][3];
                    } elseif ('nbRecyclers' == $sup and 8 == $buildingNumber) {
                        return OrbitalBaseResource::$building[$buildingNumber][$info][$level - 1][3];
                    } elseif ('nbRoutesMax' == $sup and 9 == $buildingNumber) {
                        return OrbitalBaseResource::$building[$buildingNumber][$info][$level - 1][3];
                    } else {
                        throw new \ErrorException('4e argument invalide dans getBuildingInfo de OrbitalBaseResource');
                    }
                }
            } else {
                throw new \ErrorException('2e argument invalide dans getBuildingInfo de OrbitalBaseResource');
            }
        } else {
            throw new \ErrorException('1er argument invalide (entre 0 et 7) dans getBuildingInfo de OrbitalBaseResource');
        }

        return false;
    }

    public function haveRights($buildingId, $level, $type, $sup)
    {
        if ($this->isABuilding($buildingId)) {
            switch ($type) {
                // assez de ressources pour contruire ?
                case 'resource':
                    return ($sup < $this->getBuildingInfo($buildingId, 'level', $level, 'resourcePrice')) ? false : true;
                    break;
                // encore de la place dans la queue ?
                // $sup est le nombre de batiments dans la queue
                case 'queue':
                    // $buildingId n'est pas utilisé
                    return ($sup < $this->getBuildingInfo($buildingId, 'level', $level, 'nbQueues')) ? true : false;
                    break;
                // droit de construire le batiment ?
                // $sup est un objet de type OrbitalBase
                case 'buildingTree':
                    $diminution = null;
                    switch ($buildingId) {
                        case OrbitalBaseResource::GENERATOR:
                            $diminution = 0;
                            break;
                        case OrbitalBaseResource::REFINERY:
                            $diminution = 0;
                            break;
                        case OrbitalBaseResource::DOCK1:
                            $diminution = 0;
                            break;
                        case OrbitalBaseResource::DOCK2:
                            $diminution = 20;
                            break;
                        case OrbitalBaseResource::DOCK3:
                            $diminution = 30;
                            break;
                        case OrbitalBaseResource::TECHNOSPHERE:
                            $diminution = 0;
                            break;
                        case OrbitalBaseResource::COMMERCIAL_PLATEFORME:
                            $diminution = 10;
                            break;
                        case OrbitalBaseResource::STORAGE:
                            $diminution = 0;
                            break;
                        case OrbitalBaseResource::RECYCLING:
                            $diminution = 10;
                            break;
                        case OrbitalBaseResource::SPATIOPORT:
                            $diminution = 20;
                            break;
                        default:
                            throw new \ErrorException('buildingId invalide (entre 0 et 9) dans haveRights de OrbitalBaseResource');
                    }
                    if (null !== $diminution) {
                        if (OrbitalBaseResource::GENERATOR == $buildingId) {
                            if ($level > OrbitalBaseResource::$building[$buildingId]['maxLevel'][$sup->typeOfBase]) {
                                return 'niveau maximum atteint';
                            } else {
                                return true;
                            }
                        } else {
                            if (1 == $level and OrbitalBase::TYP_NEUTRAL == $sup->typeOfBase and (OrbitalBaseResource::SPATIOPORT == $buildingId or OrbitalBaseResource::DOCK2 == $buildingId)) {
                                return 'vous devez évoluer votre colonie pour débloquer ce bâtiment';
                            }
                            if ($level > OrbitalBaseResource::$building[$buildingId]['maxLevel'][$sup->typeOfBase]) {
                                return 'niveau maximum atteint';
                            } elseif ($level > ($sup->realGeneratorLevel - $diminution)) {
                                return 'le niveau du générateur n\'est pas assez élevé';
                            } else {
                                return true;
                            }
                        }
                    }
                    break;
                // a la technologie pour construire ce bâtiment ?
                // $sup est un objet de type Technology
                case 'techno':
                    if (-1 == $this->getBuildingInfo($buildingId, 'techno')) {
                        return true;
                    }
                    if (1 == $sup->getTechnology($this->getBuildingInfo($buildingId, 'techno'))) {
                        return true;
                    } else {
                        return 'il vous faut développer la technologie '.$this->technologyHelper->getInfo($this->getBuildingInfo($buildingId, 'techno'), 'name');
                    }
                    break;
                default:
                    throw new \ErrorException('$type invalide (entre 1 et 4) dans haveRights de OrbitalBaseResource');
            }
        } else {
            throw new \ErrorException('buildingId invalide (entre 0 et 9) dans haveRights de OrbitalBaseResource');
        }
    }
}
