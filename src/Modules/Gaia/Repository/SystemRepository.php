<?php

namespace App\Modules\Gaia\Repository;

use App\Classes\Entity\AbstractRepository;
use App\Modules\Gaia\Domain\Repository\SystemRepositoryInterface;
use App\Modules\Gaia\Model\Sector;
use App\Modules\Gaia\Model\System;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<System>
 */
class SystemRepository extends DoctrineRepository implements SystemRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, System::class);
	}

	public function get(Uuid $id): System|null
	{
		return $this->find($id);
	}

	public function getAll(): array
	{
		return $this->findAll();
	}

	public function getSectorSystems(Sector $sector): array
	{
		return $this->findBy([
			'sector' => $sector,
		]);
	}
}
