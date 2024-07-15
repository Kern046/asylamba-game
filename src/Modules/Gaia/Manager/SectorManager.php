<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Manager;

use App\Classes\Redis\RedisManager;
use App\Modules\Athena\Domain\Repository\OrbitalBaseRepositoryInterface;
use App\Modules\Gaia\Domain\Repository\SystemRepositoryInterface;
use App\Modules\Gaia\Model\Sector;

readonly class SectorManager
{
	public function __construct(
		private RedisManager                   $redisManager,
		private SystemRepositoryInterface      $systemRepository,
		private OrbitalBaseRepositoryInterface $orbitalBaseRepository,
		private array                          $scores = [],
	) {
	}

	/**
	 * Returns a sorted array with faction identifiers as keys and their ownership score as values
	 * The highest score is first
	 *
	 * @return array<int, int>
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

		$this->redisManager->getConnection()->set('sector:' . $sector->id, serialize($scores));

		return $scores;
	}
}
