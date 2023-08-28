<?php

namespace App\Modules\Zeus\Infrastructure\Controller;

use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Athena\Domain\Repository\ShipQueueRepositoryInterface;
use App\Modules\Athena\Manager\CommercialRouteManager;
use App\Modules\Promethee\Domain\Repository\TechnologyQueueRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		BuildingQueueRepositoryInterface $buildingQueueRepository,
		TechnologyQueueRepositoryInterface $technologyQueueRepository,
		ShipQueueRepositoryInterface $shipQueueRepository,
		CommercialRouteManager $commercialRouteManager,
		EntityManagerInterface $entityManager,
	): Response {
		// @TODO All this stuff needs to go in a dedicated service which will hold the logic
		$baseLevelPlayer = $this->getParameter('zeus.player.base_level');
		$playerMissingExperience = $baseLevelPlayer * (pow(2, ($currentPlayer->level - 1)));
		// @TODO Not quite sure that this is the next experience level. To check and rename accordingly
		$playerNextLevelExperience = $baseLevelPlayer * (pow(2, ($currentPlayer->level - 2)));
		$playerExperienceProgress = ((($currentPlayer->experience - $playerNextLevelExperience) * 200) / $playerMissingExperience);

		$playerBases = $orbitalBaseRepository->getPlayerBases($currentPlayer);

		$basesData = [];

		foreach ($playerBases as $orbitalBase) {
			$baseId = $orbitalBase->id->toRfc4122();
			$basesData[$baseId] = [
				'building_queues' => $buildingQueueRepository->getBaseQueues($orbitalBase),
				'technology_queues' => $technologyQueueRepository->getPlaceQueues($orbitalBase->place),
				'dock1_ship_queues' => $shipQueueRepository->getByBaseAndDockType($orbitalBase, 1),
				'dock2_ship_queues' => $shipQueueRepository->getByBaseAndDockType($orbitalBase, 2),
			];
			// @TODO: move it to the using part of the code and remove useless data
			if ($orbitalBase->levelSpatioport > 0) {
				$basesData[$baseId]['commercial_routes'] = $commercialRouteManager->getBaseCommercialData($orbitalBase);
			}
		}

		return $this->render('pages/zeus/profile.html.twig', [
			'player_bases' => $playerBases,
			'has_splash_mode' => 'splash' === $request->query->get('mode'),
			'player_experience' => $currentPlayer->experience,
			'player_missing_experience' => $playerMissingExperience,
			'player_experience_progress' => $playerExperienceProgress,
			'building_resource_refund' => $this->getParameter('athena.building.building_queue_resource_refund'),
			'bases_data' => $basesData,
		]);
	}
}
