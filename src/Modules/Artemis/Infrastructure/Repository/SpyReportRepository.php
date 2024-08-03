<?php

namespace App\Modules\Artemis\Infrastructure\Repository;

use App\Modules\Artemis\Domain\Repository\SpyReportRepositoryInterface;
use App\Modules\Artemis\Model\SpyReport;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<SpyReport>
 */
class SpyReportRepository extends DoctrineRepository implements SpyReportRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, SpyReport::class);
	}

	public function get(Uuid $id): SpyReport|null
	{
		return $this->find($id);
	}

	public function getSystemReports(Player $player, array $places): array
	{
		return $this->findBy([
			'player' => $player,
			'place' => $places,
		], [
			'createdAt' => 'DESC',
		], 30, 0);
	}

	public function getPlayerReports(Player $player): array
	{
		return $this->findBy([
			'player' => $player,
		], [
			'createdAt' => 'DESC',
		], 40, 0);
	}
}
