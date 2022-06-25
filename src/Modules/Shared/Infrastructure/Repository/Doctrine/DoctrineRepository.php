<?php

namespace App\Modules\Shared\Infrastructure\Repository\Doctrine;

use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template T
 * @extends ServiceEntityRepository<T>
 * @extends EntityRepositoryInterface<T>
 */
abstract class DoctrineRepository extends ServiceEntityRepository implements EntityRepositoryInterface
{
	/**
	 * @param T $entity
	 */
	public function save(object $entity): void
	{
		$this->getEntityManager()->persist($entity);
		$this->getEntityManager()->flush();
	}

	/**
	 * @param T $entity
	 */
	public function remove(object $entity): void
	{
		$this->getEntityManager()->remove($entity);
		$this->getEntityManager()->flush();
	}
}
