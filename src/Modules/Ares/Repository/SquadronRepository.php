<?php

namespace App\Modules\Ares\Repository;

use App\Modules\Ares\Domain\Repository\SquadronRepositoryInterface;
use App\Modules\Ares\Model\Squadron;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<Squadron>
 */
class SquadronRepository extends DoctrineRepository implements SquadronRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Squadron::class);
	}
}
