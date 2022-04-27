<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Classes\Library\Game;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Promethee\Manager\ResearchManager;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Promethee\Manager\TechnologyQueueManager;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Promethee\Model\TechnologyId;
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewTechnosphere extends AbstractController
{
    public function __invoke(
        Request $request,
        OrbitalBase $currentBase,
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
        Player $currentPlayer,
        TechnologyManager $technologyManager,
        TechnologyQueueManager $technologyQueueManager,
        TechnologyHelper $technologyHelper,
        OrbitalBaseHelper $orbitalBaseHelper,
        ResearchManager $researchManager,
    ): Response {
        $technologyResourceRefund = $this->getParameter('promethee.technology_queue.resource_refund');

        $technology = $technologyManager->getPlayerTechnology($currentPlayer->getId());

        // session avec les technos de cette base
        $baseTechnologyQueues = $technologyQueueManager->getPlaceQueues($currentBase->getId());
        $playerTechnologyQueues = $technologyQueueManager->getPlayerQueues($currentPlayer->getId());

        $researchManager->load(['rPlayer' => $currentPlayer->getId()]);

        $coef = $currentBase->planetHistory;
        $coefBonus = Game::getImprovementFromScientificCoef($coef);
        $techBonus = $currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get(PlayerBonusId::TECHNOSPHERE_SPEED);
        $factionBonus = 0;
        if (ColorResource::APHERA == $currentPlayer->getRColor()) {
            // bonus if the player is from Aphera
            $factionBonus += ColorResource::BONUS_APHERA_TECHNO;
        }
        $totalBonus = $coefBonus + $techBonus + $factionBonus;

        return $this->render('pages/athena/base/building/technosphere.html.twig', [
            'has_financial_technologies' => in_array($currentBase->typeOfBase, [OrbitalBase::TYP_COMMERCIAL, OrbitalBase::TYP_CAPITAL]),
            'has_military_technologies' => in_array($currentBase->typeOfBase, [OrbitalBase::TYP_MILITARY, OrbitalBase::TYP_CAPITAL]),
            'base_queues' => $baseTechnologyQueues,
            'player_queues' => $playerTechnologyQueues,
            'available_queues' => $orbitalBaseHelper->getBuildingInfo(OrbitalBaseResource::TECHNOSPHERE, 'level', $currentBase->levelTechnosphere, 'nbQueues'),
            'total_bonus' => $totalBonus,
            'technology_resource_refund' => $technologyResourceRefund,
            'technologies_data' => $this->getTechnologiesData(
                $currentPlayer,
                $currentBase,
                $researchManager,
                $technologyHelper,
                $technology,
                $baseTechnologyQueues,
                $playerTechnologyQueues,
                $totalBonus,
            ),
        ]);
    }

    /**
     * @param list<TechnologyQueue> $baseTechnologyQueues
     * @param list<TechnologyQueue> $playerTechnologyQueues
     *
     * @throws \App\Classes\Exception\ErrorException
     */
    private function getTechnologiesData(
        Player $currentPlayer,
        OrbitalBase $currentBase,
        ResearchManager $researchManager,
        TechnologyHelper $technologyHelper,
        Technology $technology,
        array $baseTechnologyQueues,
        array $playerTechnologyQueues,
        int $totalBonus,
    ): array {
        $data = [];

        foreach (TechnologyId::getAll() as $technologyId) {
            if ($technologyHelper->isATechnologyNotDisplayed($technologyId)) {
                continue;
            }
            $disability = 'disable';
            $closed = '';
            $inQueue = false;
            $inALocalQueue = false;
            $isAnUnblockingTechnology = $technologyHelper->isAnUnblockingTechnology($technologyId);

            foreach ($playerTechnologyQueues as $playerQueue) {
                if ($playerQueue->getTechnology() !== $technologyId) {
                    continue;
                }
                $inQueue = true;
                foreach ($baseTechnologyQueues as $baseQueue) {
                    if ($baseQueue->getTechnology() === $technologyId) {
                        $inALocalQueue = true;
                        break;
                    }
                }
                break;
            }

            $technologyLevel = $technology->getTechnology($technologyId);
            $nextLevel = $technologyLevel + 1;
            if ($isAnUnblockingTechnology) {
                $researchRequirements = $technologyHelper->haveRights($technologyId, 'research', 1, $researchManager->getResearchList($researchManager->get()));
            } else {
                $researchRequirements = $technologyHelper->haveRights($technologyId, 'research', $nextLevel, $researchManager->getResearchList($researchManager->get()));
            }

            // compute time to build with the bonuses
            $timeToBuild = $technologyHelper->getInfo($technologyId, 'time', $nextLevel);
            $timeToBuild -= round($timeToBuild * $totalBonus / 100);
            // warning : $totalBonus est défini plus haut (ne pas inverser les blocs de code !)

            $column = $technologyHelper->getInfo($technologyId, 'column');

            $data[$column] ??= [];
            $data[$column][] = [
                'identifier' => $technologyId,
                'is_unblocking_technology' => $isAnUnblockingTechnology,
                'technology_level' => $technologyLevel,
                'next_level' => $nextLevel,
                'max_level_requirements' => $technologyHelper->haveRights($technologyId, 'maxLevel', $nextLevel),
                'queue_requirements' => $technologyHelper->haveRights($technologyId, 'queue', $currentBase, count($baseTechnologyQueues)),
                'credit_requirements' => $technologyHelper->haveRights($technologyId, 'credit', $nextLevel, $currentPlayer->getCredit()),
                'resource_requirements' => $technologyHelper->haveRights($technologyId, 'resource', $nextLevel, $currentBase->getResourcesStorage()),
                'technosphere_requirements' => $technologyHelper->haveRights($technologyId, 'technosphereLevel', $currentBase->getLevelTechnosphere()),
                'research_requirements' => $researchRequirements,
                'in_queue' => $inQueue,
                'in_local_queue' => $inALocalQueue,
                'is_over' => $isAnUnblockingTechnology && $technologyLevel,
                'time_to_build' => $timeToBuild,
            ];
        }

        return $data;
    }
}
