<?php

namespace App\Modules\Atlas\Repository;

use App\Modules\Atlas\Domain\Repository\RankingRepositoryInterface;
use App\Modules\Atlas\Model\Ranking;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<Ranking>
 */
class RankingRepository extends DoctrineRepository implements RankingRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Ranking::class);
	}

	public function hasBeenAlreadyProcessed(bool $isPlayer, bool $isFaction): bool
	{
		$qb = $this->createQueryBuilder('r');

		$qb
			->select('COUNT(r.id)')
			->where('r.isPlayer = :is_player')
			->andWhere('r.isFaction = :is_faction')
			->andWhere('r.createdAt >= NOW()')
			->setParameter('is_player', $isPlayer)
			->setParameter('is_faction', $isFaction);

		return $qb->getQuery()->getSingleScalarResult() > 0;
	}
}
