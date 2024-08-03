<?php

namespace App\Modules\Athena\Repository;

use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<CommercialShipping>
 */
class CommercialShippingRepository extends DoctrineRepository implements CommercialShippingRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, CommercialShipping::class);
	}

	public function get(Uuid $id): CommercialShipping|null
	{
		return $this->find($id);
	}

	public function getByTransaction(Transaction $transaction): CommercialShipping|null
	{
		return $this->findOneBy([
			'transaction' => $transaction,
		]);
	}

	public function getAll(): array
	{
		return $this->findAll();
	}

	public function getMoving(): array
	{
		return $this->findBy([
			'statement' => [CommercialShipping::ST_GOING, CommercialShipping::ST_MOVING_BACK],
		]);
	}

	public function getByBase(OrbitalBase $orbitalBase): array
	{
		$qb = $this->createQueryBuilder('cs');

		return $qb
			->where(
				$qb->expr()->orX(
					$qb->expr()->eq('cs.originBase', ':base'),
					$qb->expr()->eq('cs.destinationBase', ':base'),
				),
			)
			->setParameter('base', $orbitalBase->id, UuidType::NAME)
			->getQuery()
			->getResult();
	}
}
