<?php

namespace App\Modules\Athena\Repository;

use App\Modules\Athena\Domain\Repository\RecyclingMissionRepositoryInterface;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\RecyclingMission;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<RecyclingMission>
 */
class RecyclingMissionRepository extends DoctrineRepository implements RecyclingMissionRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, RecyclingMission::class);
	}

	public function get(Uuid $id): RecyclingMission|null
	{
		return $this->find($id);
	}

	public function getAll(): array
	{
		return $this->findAll();
	}

	public function getBaseMissions(OrbitalBase $base): array
	{
		return $this->findBy(['base' => $base]);
	}

	public function getBaseActiveMissions(OrbitalBase $base): array
	{
		return $this->findBy([
			'base' => $base,
			'statement' => [RecyclingMission::ST_ACTIVE, RecyclingMission::ST_BEING_DELETED],
		]);
	}

	public function removeBaseMissions(OrbitalBase $base): void
	{
		$qb = $this->createQueryBuilder('rm');

		$qb
			->delete()
			->where('rm.base = :base')
			->setParameter('base', $base);

		$qb->getQuery()->getResult();
	}
}
