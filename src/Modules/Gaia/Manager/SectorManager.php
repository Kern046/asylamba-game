<?php

namespace App\Modules\Gaia\Manager;

use App\Classes\Redis\RedisManager;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SectorRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SystemRepositoryInterface;
use App\Modules\Gaia\Model\Sector;

class SectorManager
{
	public function __construct(
		private readonly RedisManager $redisManager,
		private readonly SectorRepositoryInterface $sectorRepository,
		private readonly SystemRepositoryInterface $systemRepository,
		private readonly OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		private readonly array $scores = [],
	) {
	}

	public function initOwnershipData()
	{
		// $this->loadBalancer->affectTask(
		//    $this->taskManager->createTechnicalTask('gaia.sector_manager', 'calculateAllOwnerships')
		// );
	}

	public function calculateAllOwnerships()
	{
		foreach ($this->sectorRepository->getAll() as $sector) {
			$this->calculateOwnership($sector);
		}
	}

	/**
	 * @return array
	 */
	public function calculateOwnership(Sector $sector): array
	{
		$systems = $this->systemRepository->getSectorSystems($sector);
		$bases = $this->orbitalBaseRepository->getSectorBases($sector);
		$scores = [];

		foreach ($bases as $base) {
			$player = $base->player;

			$scores[$player->faction->identifier] =
				(!empty($scores[$player->faction->identifier]))
				? $scores[$player->faction->identifier] + $this->scores[$base->typeOfBase]
				: $this->scores[$base->typeOfBase]
			;
		}
		// For each system, the owning faction gains two points
		foreach ($systems as $system) {
			if (null === $system->faction) {
				continue;
			}
			$scores[$system->faction->identifier] = (!empty($scores[$system->faction->identifier]))
				? $scores[$system->faction->identifier] + 2
				: 2;
		}
		$scores[0] = 0;
		arsort($scores);
		reset($scores);

		$this->redisManager->getConnection()->set('sector:'.$sector->id, serialize($scores));

		return $scores;
	}
}
