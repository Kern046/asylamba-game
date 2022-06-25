<?php

namespace App\Modules\Athena\Repository;

use App\Modules\Athena\Domain\Repository\RecyclingLogRepositoryInterface;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\RecyclingLog;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<RecyclingLog>
 */
class RecyclingLogRepository extends DoctrineRepository implements RecyclingLogRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, RecyclingLog::class);
	}

	public function getBaseActiveMissionsLogs(OrbitalBase $base): array
	{
		$qb = $this->createQueryBuilder('rl');

		$qb
			->join('rl.mission', 'rm')
			->where('rm.base = :base')
			->setParameter('base', $base)
			->orderBy('rl.createdAt', 'DESC');

		return $qb->getQuery()->getResult();
	}
}
