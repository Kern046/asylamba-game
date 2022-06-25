<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Modules\Athena\Application\Handler\Building\BuildingLevelHandler;
use App\Modules\Athena\Domain\Repository\BuildingQueueRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Promethee\Domain\Repository\TechnologyRepositoryInterface;
use App\Modules\Promethee\Manager\TechnologyManager;
use App\Modules\Promethee\Model\Technology;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewGenerator extends AbstractController
{
	public function __construct(
		private readonly BuildingLevelHandler $buildingLevelHandler,
		private readonly OrbitalBaseHelper $orbitalBaseHelper,
		private readonly BuildingQueueRepositoryInterface $buildingQueueRepository,
	) {

	}

	public function __invoke(
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		Player $currentPlayer,
		OrbitalBase $currentBase,
		TechnologyManager $technologyManager,
		TechnologyRepositoryInterface $technologyRepository,
	): Response {
		$technology = $technologyRepository->getPlayerTechnology($currentPlayer);

		return $this->render('pages/athena/generator.html.twig', [
			'technology' => $technology,
			'generator_speed_bonus' => $currentPlayerBonusRegistry
				->getPlayerBonus()->bonuses->get(PlayerBonusId::GENERATOR_SPEED),
			'building_resource_refund' => $this->getParameter('athena.building.building_queue_resource_refund'),
			'buildings_data' => $this->getBuildingsData($currentBase, $technology),
		]);
	}

	private function getBuildingsData(OrbitalBase $currentBase, Technology $technology): array
	{
		$data = [];
		$buildingQueues = $this->buildingQueueRepository->getBaseQueues($currentBase);

		foreach (OrbitalBaseResource::$building as $buildingNumber => $buildingData) {
			$level = $this->buildingLevelHandler->getBuildingLevel($currentBase, $buildingNumber);
			$realLevel = $this->buildingLevelHandler->getBuildingRealLevel(
				$currentBase,
				$buildingNumber,
				$buildingQueues,
			);
			$nextLevel = $realLevel + 1;

			$data[$buildingNumber] = [
				'real_level' => $realLevel,
				'next_level' => $nextLevel,
				'level' => $level,
				'building_requirements' => $this->orbitalBaseHelper->haveRights($buildingNumber, $nextLevel, 'buildingTree', $currentBase),
				'technology_requirements' => $this->orbitalBaseHelper->haveRights($buildingNumber, $nextLevel, 'techno', $technology),
				'queue_requirements' => $this->orbitalBaseHelper->haveRights(
					OrbitalBaseResource::GENERATOR,
					$currentBase->levelGenerator,
					'queue',
					count(
						$buildingQueues,
					)
				),
				'resources_requirements' => $this->orbitalBaseHelper->haveRights($buildingNumber, $nextLevel, 'resource', $currentBase->resourcesStorage),
			];
		}

		return $data;
	}
}
