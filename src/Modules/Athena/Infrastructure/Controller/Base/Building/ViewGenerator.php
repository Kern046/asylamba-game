<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewGenerator extends AbstractController
{
    public function __invoke(
        CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
        Player $currentPlayer,
        OrbitalBase $currentBase,
        OrbitalBaseHelper $orbitalBaseHelper,
        TechnologyManager $technologyManager,
    ): Response {
        $technology = $technologyManager->getPlayerTechnology($currentPlayer->getId());

        return $this->render('pages/athena/generator.html.twig', [
            'technology' => $technology,
            'generator_speed_bonus' => $currentPlayerBonusRegistry
                ->getPlayerBonus()->bonuses->get(PlayerBonusId::GENERATOR_SPEED),
            'building_resource_refund' => $this->getParameter('athena.building.building_queue_resource_refund'),
            'buildings_data' => $this->getBuildingsData($orbitalBaseHelper, $currentBase, $technology),
        ]);
    }

    private function getBuildingsData(
        OrbitalBaseHelper $orbitalBaseHelper,
        OrbitalBase $currentBase,
        Technology $technology,
    ): array {
        $data = [];
        foreach (OrbitalBaseResource::$building as $buildingNumber => $buildingData) {
            $level = $currentBase->{'getLevel'.$buildingData['name']}();
            $realLevel = $currentBase->{'getReal'.$buildingData['name'].'Level'}();
            $nextLevel = $realLevel + 1;

            $data[$buildingNumber] = [
                'real_level' => $realLevel,
                'next_level' => $nextLevel,
                'level' => $level,
                'building_requirements' => $orbitalBaseHelper->haveRights($buildingNumber, $nextLevel, 'buildingTree', $currentBase),
                'technology_requirements' => $orbitalBaseHelper->haveRights($buildingNumber, $nextLevel, 'techno', $technology),
                'queue_requirements' => $orbitalBaseHelper->haveRights(
                    OrbitalBaseResource::GENERATOR,
                    $currentBase->getLevelGenerator(),
                    'queue',
                    count($currentBase->buildingQueues,
                )),
                'resources_requirements' => $orbitalBaseHelper->haveRights($buildingNumber, $nextLevel, 'resource', $currentBase->getResourcesStorage()),
            ];
        }

        return $data;
    }
}
