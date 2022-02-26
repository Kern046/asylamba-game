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
use App\Modules\Promethee\Model\TechnologyQueue;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewTechnosphere extends AbstractController
{
	public function __invoke(
		Request $request,
		OrbitalBase $currentBase,
		Player $currentPlayer,
		TechnologyManager $technologyManager,
		TechnologyQueueManager $technologyQueueManager,
		TechnologyHelper $technologyHelper,
		OrbitalBaseHelper $orbitalBaseHelper,
		ResearchManager $researchManager,
	): Response {
		$session = $request->getSession();
		$technologyResourceRefund = $this->getParameter('promethee.technology_queue.resource_refund');

		$technology = $technologyManager->getPlayerTechnology($currentPlayer->getId());

		# session avec les technos de cette base
		$baseTechnologyQueues = $technologyQueueManager->getPlaceQueues($currentBase->getId());
		$playerTechnologyQueues = $technologyQueueManager->getPlayerQueues($currentPlayer->getId());

		$researchManager->load(array('rPlayer' => $currentPlayer->getId()));


		$coef = $currentBase->planetHistory;
		$coefBonus = Game::getImprovementFromScientificCoef($coef);
		$techBonus = $session->get('playerBonus')->get(PlayerBonus::TECHNOSPHERE_SPEED);
		$factionBonus = 0;
		if ($currentPlayer->getRColor() == ColorResource::APHERA) {
			# bonus if the player is from Aphera
			$factionBonus += ColorResource::BONUS_APHERA_TECHNO;
		}
		$totalBonus = $coefBonus + $techBonus + $factionBonus;

		return $this->render('pages/athena/base/building/technosphere.html.twig', [
			'has_financial_technologies' => in_array($currentBase->typeOfBase, array(OrbitalBase::TYP_COMMERCIAL, OrbitalBase::TYP_CAPITAL)),
			'has_military_technologies' => in_array($currentBase->typeOfBase, array(OrbitalBase::TYP_MILITARY, OrbitalBase::TYP_CAPITAL)),
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
			)
		]);
	}

	/**
	 * @param list<TechnologyQueue> $baseTechnologyQueues
	 * @param list<TechnologyQueue> $playerTechnologyQueues
	 * @return array
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

		for ($i = 0; $i < Technology::QUANTITY; $i++) {
			if ($technologyHelper->isATechnologyNotDisplayed($i)) {
				continue;
			}
			$disability = 'disable';
			$closed = '';
			$inQueue = false;
			$inALocalQueue = false;
			$isAnUnblockingTechnology = $technologyHelper->isAnUnblockingTechnology($i);

			foreach ($playerTechnologyQueues as $playerQueue) {
				if ($playerQueue->getTechnology() !== $i) {
					continue;
				}
				$inQueue = true;
				foreach ($baseTechnologyQueues as $baseQueue) {
					if ($baseQueue->getTechnology() === $i) {
						$inALocalQueue = true;
						break;
					}
				}
				break;
			}

			$technologyLevel = $technology->getTechnology($i);
			$nextLevel = $technologyLevel + 1;
			if ($isAnUnblockingTechnology) {
				$researchRequirements = $technologyHelper->haveRights($i, 'research', 1, $researchManager->getResearchList($researchManager->get()));
			} else {
				$researchRequirements = $technologyHelper->haveRights($i, 'research', $nextLevel, $researchManager->getResearchList($researchManager->get()));
			}

			# compute time to build with the bonuses
			$timeToBuild = $technologyHelper->getInfo($i, 'time', $nextLevel);
			$timeToBuild -= round($timeToBuild * $totalBonus / 100);
			# warning : $totalBonus est défini plus haut (ne pas inverser les blocs de code !)

			$column = $technologyHelper->getInfo($i, 'column');

			$data[$column] ??= [];
			$data[$column][] = [
				'identifier' => $i,
				'is_unblocking_technology' => $isAnUnblockingTechnology,
				'technology_level' => $technologyLevel,
				'next_level' => $nextLevel,
				'max_level_requirements' => $technologyHelper->haveRights($i, 'maxLevel', $nextLevel),
				'queue_requirements' => $technologyHelper->haveRights($i, 'queue', $currentBase, count($baseTechnologyQueues)),
				'credit_requirements' => $technologyHelper->haveRights($i, 'credit', $nextLevel, $currentPlayer->getCredit()),
				'resource_requirements' => $technologyHelper->haveRights($i, 'resource', $nextLevel, $currentBase->getResourcesStorage()),
				'technosphere_requirements' => $technologyHelper->haveRights($i, 'technosphereLevel', $currentBase->getLevelTechnosphere()),
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
