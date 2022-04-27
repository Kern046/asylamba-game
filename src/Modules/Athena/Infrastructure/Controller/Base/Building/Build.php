<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Classes\Library\Utils;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\BuildingQueueManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Build extends AbstractController
{
    public function __invoke(
        Request $request,
        Player $currentPlayer,
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
        OrbitalBase $currentBase,
        OrbitalBaseHelper $orbitalBaseHelper,
        OrbitalBaseManager $orbitalBaseManager,
        TechnologyManager $technologyManager,
        BuildingQueueManager $buildingQueueManager,
        int $identifier,
    ): Response {
        if ($orbitalBaseHelper->isABuilding($identifier)) {
            $buildingQueues = $buildingQueueManager->getBaseQueues($currentBase->getId());

            $currentLevel = call_user_func([$currentBase, 'getReal'.ucfirst($orbitalBaseHelper->getBuildingInfo($identifier, 'name')).'Level']);
            $technos = $technologyManager->getPlayerTechnology($currentPlayer->getId());

            if ($orbitalBaseHelper->haveRights($identifier, $currentLevel + 1, 'resource', $currentBase->getResourcesStorage())
                and $orbitalBaseHelper->haveRights(OrbitalBaseResource::GENERATOR, $currentBase->getLevelGenerator(), 'queue', count($buildingQueues))
                and (true === $orbitalBaseHelper->haveRights($identifier, $currentLevel + 1, 'buildingTree', $currentBase))
                and $orbitalBaseHelper->haveRights($identifier, $currentLevel + 1, 'techno', $technos)) {
                $session = $request->getSession();

                // build the new building
                $bq = new BuildingQueue();
                $bq->rOrbitalBase = $currentBase->getId();
                $bq->buildingNumber = $identifier;
                $bq->targetLevel = $currentLevel + 1;
                $time = $orbitalBaseHelper->getBuildingInfo($identifier, 'level', $currentLevel + 1, 'time');
                $bonus = $time * $currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get(PlayerBonusId::GENERATOR_SPEED) / 100;
                $nbBuildingQueues = count($buildingQueues);
                if (0 === $nbBuildingQueues) {
                    $bq->dStart = Utils::now();
                } else {
                    $bq->dStart = $buildingQueues[$nbBuildingQueues - 1]->dEnd;
                }
                $bq->dEnd = Utils::addSecondsToDate($bq->dStart, round($time - $bonus));
                $buildingQueueManager->add($bq, $currentPlayer);

                // debit resources
                $orbitalBaseManager->decreaseResources($currentBase, $orbitalBaseHelper->getBuildingInfo($identifier, 'level', $currentLevel + 1, 'resourcePrice'));

                //						if ($container->getParameter('data_analysis')) {
                //							$qr = $database->prepare('INSERT INTO
                //						DA_BaseAction(`from`, type, opt1, opt2, weight, dAction)
                //						VALUES(?, ?, ?, ?, ?, ?)'
                //							);
                //							$qr->execute([$session->get('playerId'), 1, $building, $currentLevel + 1, DataAnalysis::resourceToStdUnit($orbitalBaseHelper->getBuildingInfo($building, 'level', $currentLevel + 1, 'resourcePrice')), Utils::now()]);
                //						}

                // add the event in controller
                $session->get('playerEvent')->add($bq->dEnd, $this->getParameter('event_base'), $currentBase->getId());

                $this->addFlash('success', 'Construction programmée');

                return $this->redirect($request->headers->get('referer'));
            } else {
                throw new ConflictHttpException('les conditions ne sont pas remplies pour construire ce bâtiment');
            }
        } else {
            throw new BadRequestHttpException('le bâtiment indiqué n\'est pas valide');
        }
    }
}
