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
use App\Modules\Zeus\Helper\TutorialHelper;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Resource\TutorialResource;
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
		OrbitalBase $currentBase,
		OrbitalBaseHelper $orbitalBaseHelper,
		OrbitalBaseManager $orbitalBaseManager,
		TechnologyManager $technologyManager,
		BuildingQueueManager $buildingQueueManager,
		TutorialHelper $tutorialHelper,
		int $identifier,
	): Response {
		if ($orbitalBaseHelper->isABuilding($identifier)) {
			$buildingQueues = $buildingQueueManager->getBaseQueues($currentBase->getId());

			$currentLevel = call_user_func(array($currentBase, 'getReal' . ucfirst($orbitalBaseHelper->getBuildingInfo($identifier, 'name')) . 'Level'));
			$technos = $technologyManager->getPlayerTechnology($currentPlayer->getId());

			if ($orbitalBaseHelper->haveRights($identifier, $currentLevel + 1, 'resource', $currentBase->getResourcesStorage())
				AND $orbitalBaseHelper->haveRights(OrbitalBaseResource::GENERATOR, $currentBase->getLevelGenerator(), 'queue', count($buildingQueues))
				AND ($orbitalBaseHelper->haveRights($identifier, $currentLevel + 1, 'buildingTree', $currentBase) === TRUE)
				AND $orbitalBaseHelper->haveRights($identifier, $currentLevel + 1, 'techno', $technos)) {

				# tutorial
				if ($currentPlayer->stepDone == FALSE) {
					switch ($currentPlayer->getStepTutorial()) {
						case TutorialResource::GENERATOR_LEVEL_2:
							if ($identifier == OrbitalBaseResource::GENERATOR AND $currentLevel + 1 >= 2) {
								$tutorialHelper->setStepDone();
							}
							break;
						case TutorialResource::REFINERY_LEVEL_3:
							if ($identifier == OrbitalBaseResource::REFINERY AND $currentLevel + 1 >= 3) {
								$tutorialHelper->setStepDone();
							}
							break;
						case TutorialResource::STORAGE_LEVEL_3:
							if ($identifier == OrbitalBaseResource::STORAGE AND $currentLevel + 1 >= 3) {
								$tutorialHelper->setStepDone();
							}
							break;
						case TutorialResource::DOCK1_LEVEL_1:
							if ($identifier == OrbitalBaseResource::DOCK1 AND $currentLevel + 1 >= 1) {
								$tutorialHelper->setStepDone();
							}
							break;
						case TutorialResource::TECHNOSPHERE_LEVEL_1:
							if ($identifier == OrbitalBaseResource::TECHNOSPHERE AND $currentLevel + 1 >= 1) {
								$tutorialHelper->setStepDone();
							}
							break;
						case TutorialResource::REFINERY_LEVEL_10:
							if ($identifier == OrbitalBaseResource::REFINERY AND $currentLevel + 1 >= 10) {
								$tutorialHelper->setStepDone();
							}
							break;
						case TutorialResource::STORAGE_LEVEL_8:
							if ($identifier == OrbitalBaseResource::STORAGE AND $currentLevel + 1 >= 8) {
								$tutorialHelper->setStepDone();
							}
							break;
						case TutorialResource::DOCK1_LEVEL_6:
							if ($identifier == OrbitalBaseResource::DOCK1 AND $currentLevel + 1 >= 6) {
								$tutorialHelper->setStepDone();
							}
							break;
						case TutorialResource::REFINERY_LEVEL_16:
							if ($identifier == OrbitalBaseResource::REFINERY AND $currentLevel + 1 >= 16) {
								$tutorialHelper->setStepDone();
							}
							break;
						case TutorialResource::STORAGE_LEVEL_12:
							if ($identifier == OrbitalBaseResource::STORAGE AND $currentLevel + 1 >= 12) {
								$tutorialHelper->setStepDone();
							}
							break;
						case TutorialResource::TECHNOSPHERE_LEVEL_6:
							if ($identifier == OrbitalBaseResource::TECHNOSPHERE AND $currentLevel + 1 >= 6) {
								$tutorialHelper->setStepDone();
							}
							break;
						case TutorialResource::DOCK1_LEVEL_15:
							if ($identifier == OrbitalBaseResource::DOCK1 AND $currentLevel + 1 >= 15) {
								$tutorialHelper->setStepDone();
							}
							break;
						case TutorialResource::REFINERY_LEVEL_20:
							if ($identifier == OrbitalBaseResource::REFINERY AND $currentLevel + 1 >= 20) {
								$tutorialHelper->setStepDone();
							}
							break;
					}
				}

				$session = $request->getSession();

				# build the new building
				$bq = new BuildingQueue();
				$bq->rOrbitalBase = $currentBase->getId();
				$bq->buildingNumber = $identifier;
				$bq->targetLevel = $currentLevel + 1;
				$time = $orbitalBaseHelper->getBuildingInfo($identifier, 'level', $currentLevel + 1, 'time');
				$bonus = $time * $session->get('playerBonus')->get(PlayerBonus::GENERATOR_SPEED) / 100;
				$nbBuildingQueues = count($buildingQueues);
				if ($nbBuildingQueues === 0) {
					$bq->dStart = Utils::now();
				} else {
					$bq->dStart = $buildingQueues[$nbBuildingQueues - 1]->dEnd;
				}
				$bq->dEnd = Utils::addSecondsToDate($bq->dStart, round($time - $bonus));
				$buildingQueueManager->add($bq);

				# debit resources
				$orbitalBaseManager->decreaseResources($currentBase, $orbitalBaseHelper->getBuildingInfo($identifier, 'level', $currentLevel + 1, 'resourcePrice'));

//						if ($container->getParameter('data_analysis')) {
//							$qr = $database->prepare('INSERT INTO
//						DA_BaseAction(`from`, type, opt1, opt2, weight, dAction)
//						VALUES(?, ?, ?, ?, ?, ?)'
//							);
//							$qr->execute([$session->get('playerId'), 1, $building, $currentLevel + 1, DataAnalysis::resourceToStdUnit($orbitalBaseHelper->getBuildingInfo($building, 'level', $currentLevel + 1, 'resourcePrice')), Utils::now()]);
//						}

				# add the event in controller
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
