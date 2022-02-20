<?php

namespace App\Modules\Zeus\Infrastructure\Controller\Tutorial;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\FormException;
use App\Classes\Library\Format;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\ShipQueueManager;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Zeus\Helper\TutorialHelper;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateStep extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBaseManager $orbitalBaseManager,
		PlayerManager $playerManager,
		TutorialHelper $tutorialHelper,
		ShipQueueManager $shipQueueManager,
		EntityManager $entityManager,
	): Response {
		$playerId = $currentPlayer->getId();
		$stepTutorial = $currentPlayer->getStepTutorial();
		$session = $request->getSession();

		if (true == $currentPlayer->stepDone AND TutorialResource::stepExists($stepTutorial)) {
			$experience = TutorialResource::getInfo($stepTutorial, 'experienceReward');
			$credit = TutorialResource::getInfo($stepTutorial, 'creditReward');
			$resource = TutorialResource::getInfo($stepTutorial, 'resourceReward');
			$ship = TutorialResource::getInfo($stepTutorial, 'shipReward');
			$playerBases = $orbitalBaseManager->getPlayerBases($currentPlayer->getId());
			$alert = 'Etape validée. ';

			$firstReward = true;
			if ($experience > 0) {
				$firstReward = false;
				$alert .= 'Vous gagnez ' . $experience . ' points d\'expérience';
				$playerManager->increaseExperience($currentPlayer, $experience);
			}

			if ($credit > 0) {
				if ($firstReward) {
					$firstReward = false;
					$alert .= 'Vous gagnez ' . $credit . 'crédits';
				} else {
					$alert .= ', ainsi que ' . $credit . ' crédits';
				}
				$playerManager->increaseCredit($currentPlayer, $credit);
			}

			if ($resource > 0 || $ship != array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)) {
				$ob = $playerBases[0];

				if ($resource > 0) {
					if ($firstReward) {
						$firstReward = false;
						$alert .= 'Vous gagnez ' . $resource . ' ressources';
					} else {
						$alert .= ' et ' . $resource . ' ressources';
					}
					$alert .= ' sur votre base orbitale ' . $ob->name . '. ';
					$orbitalBaseManager->increaseResources($ob, $resource, TRUE);
				}

				if ($ship != array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)) {
					$qty = 0;
					$ships = array();
					foreach ($ship as $key => $value) {
						if ($value != 0) {
							$ships[$qty] = array();
							$ships[$qty]['quantity'] = $value;
							$ships[$qty]['name'] = ShipResource::getInfo($key, 'codeName');
							$qty++;

							# add ship to dock
							$orbitalBaseManager->addShipToDock($ob, $key, $value);
						}
					}
					if ($firstReward) {
						$firstReward = false;
						$alert .= 'Vous gagnez ';
						$endOfAlert = ' sur votre base orbitale ' . $ob->name . '. ';
					} else {
						$alert .= '. Vous gagnez également ';
						$endOfAlert = '. ';
					}

					# complete alert
					foreach ($ships as $key => $value) {
						if ($key == 0) {
							$alert .= $value['quantity'] . ' ' . $value['name'] . Format::plural($value['quantity']);
						} else if ($qty - 1 == $key) {
							$alert .= ' et ' . $value['quantity'] . ' ' . $value['name'] . Format::plural($value['quantity']);
						} else {
							$alert .= ', ' . $value['quantity'] . ' ' . $value['name'] . Format::plural($value['quantity']);
						}
					}
					$alert .= $endOfAlert;
				}
			} else {
				$alert .= '. ';
			}

			$alert .= 'La prochaine étape vous attend.';
			$this->addFlash('success', $alert);

			$nextStep = $stepTutorial;
			if (TutorialResource::isLastStep($stepTutorial)) {
				$nextStep = 0;
				$this->addFlash('success', 'Bravo, vous avez terminé le tutoriel. Bonne continuation et bon amusement sur Asylamba, vous pouvez maintenant voler de vos propres ailes !');
			} else {
				$nextStep += 1;
			}

			# verify if the next step is already done
			$nextStepAlreadyDone = FALSE;
			$redirectWithoutJeanMi = FALSE;
			switch ($nextStep) {
				case TutorialResource::NAVIGATION :
					$redirectWithoutJeanMi = TRUE;
					$nextStepAlreadyDone = TRUE;
					break;
				case TutorialResource::GENERATOR_LEVEL_2 :
					$nextStepAlreadyDone = $tutorialHelper->isNextBuildingStepAlreadyDone($playerId, OrbitalBaseResource::GENERATOR, 2);
					break;
				case TutorialResource::REFINERY_LEVEL_3 :
					$nextStepAlreadyDone = $tutorialHelper->isNextBuildingStepAlreadyDone($playerId, OrbitalBaseResource::REFINERY, 3);
					break;
				case TutorialResource::STORAGE_LEVEL_3 :
					$nextStepAlreadyDone = $tutorialHelper->isNextBuildingStepAlreadyDone($playerId, OrbitalBaseResource::STORAGE, 3);
					break;
				case TutorialResource::TECHNOSPHERE_LEVEL_1 :
					$nextStepAlreadyDone = $tutorialHelper->isNextBuildingStepAlreadyDone($playerId, OrbitalBaseResource::TECHNOSPHERE, 1);
					break;
				case TutorialResource::MODIFY_UNI_INVEST:
					# asdf
					break;
				case TutorialResource::CREATE_COMMANDER:
					# asdf
					break;
				case TutorialResource::DOCK1_LEVEL_1 :
					$nextStepAlreadyDone = $tutorialHelper->isNextBuildingStepAlreadyDone($playerId, OrbitalBaseResource::DOCK1, 1);
					break;
				case TutorialResource::SHIP0_UNBLOCK :
					$nextStepAlreadyDone = $tutorialHelper->isNextTechnoStepAlreadyDone($playerId, Technology::SHIP0_UNBLOCK);
					break;
				case TutorialResource::BUILD_SHIP0 :
					# verify in the queue
					# load the queues
					foreach ($playerBases as $ob) {
						$shipQueues = $shipQueueManager->getBaseQueues($ob->rPlace);
						foreach ($shipQueues as $shipQueue) {
							if ($shipQueue->shipNumber == ShipResource::PEGASE) {
								$nextStepAlreadyDone = TRUE;
								break;
							}
						}
					}
					break;
				case TutorialResource::AFFECT_COMMANDER:
					# asdf
					break;
				case TutorialResource::FILL_SQUADRON :
					# asdf
					break;
				case TutorialResource::MOVE_FLEET_LINE :
					# asdf
					break;
				case TutorialResource::SPY_PLANET :
					# asdf
					break;
				case TutorialResource::LOOT_PLANET :
					# asdf
					break;
				case TutorialResource::FACTION_FORUM :
					# asdf
					break;
				case TutorialResource::SHARE_ASYLAMBA :
					$nextStepAlreadyDone = TRUE;
					break;
				case TutorialResource::REFINERY_LEVEL_10 :
					$nextStepAlreadyDone = $tutorialHelper->isNextBuildingStepAlreadyDone($playerId, OrbitalBaseResource::REFINERY, 10);
					break;
				case TutorialResource::STORAGE_LEVEL_8 :
					$nextStepAlreadyDone = $tutorialHelper->isNextBuildingStepAlreadyDone($playerId, OrbitalBaseResource::STORAGE, 8);
					break;
				case TutorialResource::DOCK1_LEVEL_6 :
					$nextStepAlreadyDone = $tutorialHelper->isNextBuildingStepAlreadyDone($playerId, OrbitalBaseResource::DOCK1, 6);
					break;
				case TutorialResource::REFINERY_LEVEL_16 :
					$nextStepAlreadyDone = $tutorialHelper->isNextBuildingStepAlreadyDone($playerId, OrbitalBaseResource::REFINERY, 16);
					break;
				case TutorialResource::STORAGE_LEVEL_12 :
					$nextStepAlreadyDone = $tutorialHelper->isNextBuildingStepAlreadyDone($playerId, OrbitalBaseResource::STORAGE, 12);
					break;
				case TutorialResource::TECHNOSPHERE_LEVEL_6 :
					$nextStepAlreadyDone = $tutorialHelper->isNextBuildingStepAlreadyDone($playerId, OrbitalBaseResource::TECHNOSPHERE, 6);
					break;
				case TutorialResource::SHIP1_UNBLOCK :
					$nextStepAlreadyDone = $tutorialHelper->isNextTechnoStepAlreadyDone($playerId, Technology::SHIP1_UNBLOCK);
					break;
				case TutorialResource::DOCK1_LEVEL_15 :
					$nextStepAlreadyDone = $tutorialHelper->isNextBuildingStepAlreadyDone($playerId, OrbitalBaseResource::DOCK1, 15);
					break;
				case TutorialResource::BUILD_SHIP1 :
					# asdf
					break;
				case TutorialResource::REFINERY_LEVEL_20 :
					$nextStepAlreadyDone = $tutorialHelper->isNextBuildingStepAlreadyDone($playerId, OrbitalBaseResource::REFINERY, 20);
					break;
				case TutorialResource::SPONSORSHIP :
					$nextStepAlreadyDone = TRUE;
					break;
			}
			if (!$nextStepAlreadyDone) {
				$currentPlayer->stepDone = 0;
				$session->get('playerInfo')->add('stepDone', FALSE);
			}
			$currentPlayer->stepTutorial = $nextStep;
			$session->get('playerInfo')->add('stepTutorial', $nextStep);
			$entityManager->flush($currentPlayer);
			if ($redirectWithoutJeanMi) {
				return $this->redirectToRoute('profile');
			}
			return $this->redirect($request->headers->get('referer'));
		} else {
			throw new FormException('Impossible de valider l\'étape avant de l\'avoir effectuée.');
		}
	}
}
