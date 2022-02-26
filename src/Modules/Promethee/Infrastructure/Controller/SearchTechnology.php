<?php

namespace App\Modules\Promethee\Infrastructure\Controller;

use App\Classes\Exception\ErrorException;
use App\Classes\Library\Game;
use App\Classes\Library\Utils;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Promethee\Helper\TechnologyHelper;
use App\Modules\Promethee\Manager\ResearchManager;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Promethee\Manager\TechnologyQueueManager;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Modules\Zeus\Helper\TutorialHelper;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Resource\TutorialResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchTechnology extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBase $currentBase,
		TechnologyHelper $technologyHelper,
		TechnologyManager $technologyManager,
		TechnologyQueueManager $technologyQueueManager,
		OrbitalBaseManager $orbitalBaseManager,
		PlayerManager $playerManager,
		TutorialHelper $tutorialHelper,
		ResearchManager $researchManager,
		string $identifier,
	): Response {
		$session = $request->getSession();
		if ($technologyHelper->isATechnology($identifier) && !$technologyHelper->isATechnologyNotDisplayed($identifier)) {
			if (($technologyQueueManager->getPlayerTechnologyQueue($currentPlayer->getId(), $identifier)) === null) {

				$technos = $technologyManager->getPlayerTechnology($currentPlayer->getId());
				$targetLevel = $technos->getTechnology($identifier) + 1;
				// @TODO I think this piece of code is dead
				$technologyQueues = $technologyQueueManager->getPlaceQueues($currentBase->getId());
				$nbTechnologyQueues = count($technologyQueues);
				foreach ($technologyQueues as $technologyQueue) {
					if ($technologyQueue->technology == $identifier) {
						$targetLevel++;
					}
				}
				$researchManager->load(array('rPlayer' => $currentPlayer->getId()));

				if ($technologyHelper->haveRights($identifier, 'resource', $targetLevel, $currentBase->getResourcesStorage())
					&& $technologyHelper->haveRights($identifier, 'credit', $targetLevel, $currentPlayer->getCredit())
					&& $technologyHelper->haveRights($identifier, 'queue', $currentBase, $nbTechnologyQueues)
					&& $technologyHelper->haveRights($identifier, 'levelPermit', $targetLevel)
					&& $technologyHelper->haveRights($identifier, 'technosphereLevel', $currentBase->getLevelTechnosphere())
					&& ($technologyHelper->haveRights($identifier, 'research', $targetLevel, $researchManager->getResearchList($researchManager->get())) === TRUE)
					&& $technologyHelper->haveRights($identifier, 'maxLevel', $targetLevel)
					&& $technologyHelper->haveRights($identifier, 'baseType', $currentBase->typeOfBase)) {

					# tutorial
					if ($currentPlayer->stepDone == FALSE) {
						switch ($currentPlayer->getStepTutorial()) {
							case TutorialResource::SHIP0_UNBLOCK:
								if ($identifier == Technology::SHIP0_UNBLOCK) {
									$tutorialHelper->setStepDone();
								}
								break;
							case TutorialResource::SHIP1_UNBLOCK:
								if ($identifier == Technology::SHIP1_UNBLOCK) {
									$tutorialHelper->setStepDone();
								}
								break;
						}
					}

					// construit la nouvelle techno
					$time = $technologyHelper->getInfo($identifier, 'time', $targetLevel);
					$bonusPercent = $session->get('playerBonus')->get(PlayerBonus::TECHNOSPHERE_SPEED);
					if ($currentPlayer->getRColor() == ColorResource::APHERA) {
						# bonus if the player is from Aphera
						$bonusPercent += ColorResource::BONUS_APHERA_TECHNO;
					}

					# ajout du bonus du lieu
					$bonusPercent += Game::getImprovementFromScientificCoef($currentBase->planetHistory);
					$bonus = round($time * $bonusPercent / 100);

					$createdAt =
						($nbTechnologyQueues === 0)
							? Utils::now()
							: $technologyQueues[$nbTechnologyQueues - 1]->getEndedAt()
					;
					$tq =
						(new TechnologyQueue())
							->setPlayerId($currentPlayer->getId())
							->setPlaceId($currentBase->getId())
							->setTechnology($identifier)
							->setTargetLevel($targetLevel)
							->setCreatedAt($createdAt)
							->setEndedAt(Utils::addSecondsToDate($createdAt, round($time - $bonus)))
					;
					$technologyQueueManager->add($tq);

					$orbitalBaseManager->decreaseResources($currentBase, $technologyHelper->getInfo($identifier, 'resource', $targetLevel));

					$playerManager->decreaseCredit($currentPlayer, $technologyHelper->getInfo($identifier, 'credit', $targetLevel));

//						if (true === $this->getContainer()->getParameter('data_analysis')) {
//							$qr = $database->prepare('INSERT INTO
//							DA_BaseAction(`from`, type, opt1, opt2, weight, dAction)
//							VALUES(?, ?, ?, ?, ?, ?)'
//							);
//							$qr->execute([$session->get('playerId'), 2, $techno, $targetLevel, (DataAnalysis::resourceToStdUnit($technologyHelper->getInfo($techno, 'resource', $targetLevel)) + DataAnalysis::creditToStdUnit($technologyHelper->getInfo($techno, 'credit', $targetLevel))), Utils::now()]);
//						}

					// alerte
					$this->addFlash('success', 'Développement de la technologie programmée');

					return $this->redirect($request->headers->get('referer'));
				} else {
					throw new ErrorException(sprintf(
						'les conditions ne sont pas remplies pour développer une technologie : ["%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s"]',
						$technologyHelper->haveRights($identifier, 'resource', $targetLevel, $currentBase->getResourcesStorage()),
						$technologyHelper->haveRights($identifier, 'credit', $targetLevel, $currentPlayer->getCredit()),
						$technologyHelper->haveRights($identifier, 'queue', $currentBase, $nbTechnologyQueues),
						$technologyHelper->haveRights($identifier, 'levelPermit', $targetLevel),
						$technologyHelper->haveRights($identifier, 'technosphereLevel', $currentBase->getLevelTechnosphere()),
						$technologyHelper->haveRights($identifier, 'research', $targetLevel, $researchManager->getResearchList($researchManager->get())),
						$technologyHelper->haveRights($identifier, 'maxLevel', $targetLevel),
						$technologyHelper->haveRights($identifier, 'baseType', $currentBase->typeOfBase),
					));
				}
			} else {
				throw new ErrorException('Cette technologie est déjà en construction');
			}
		} else {
			throw new ErrorException('la technologie indiquée n\'est pas valide');
		}
	}
}
