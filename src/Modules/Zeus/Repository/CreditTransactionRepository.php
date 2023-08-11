<?php

namespace App\Modules\Zeus\Repository;

use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Domain\Repository\CreditTransactionRepositoryInterface;
use App\Modules\Zeus\Model\CreditHolderInterface;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\FactionToPlayerCreditTransaction;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerToFactionCreditTransaction;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<CreditTransaction>
 */
class CreditTransactionRepository extends DoctrineRepository implements CreditTransactionRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, CreditTransaction::class);
	}

	public function getAllBySender(CreditHolderInterface $sender): array
	{
		return $this->findBy([
			'sender' => $sender,
		], [
			'createdAt' => 'DESC',
		], 20);
	}

	public function getAllByPlayerReceiver(Player $player): array
	{
		return $this->findBy([
			'receiver' => $player,
		], [
			'createdAt' => 'DESC',
		], 20);
	}

	public function getAllByFactionReceiverFromMembers(Color $faction): array
	{
		$qb = $this->getEntityManager()->createQueryBuilder();

		$qb->select('ct')
			->from(PlayerToFactionCreditTransaction::class, 'ct')
			->where('ct.receiver = :faction')
			->orderBy('ct.createdAt', 'DESC')
			->setMaxResults(20)
			->setParameter('faction', $faction);

		return $qb->getQuery()->getResult();
	}

	public function getAllByFactionReceiverFromFactions(Color $faction): array
	{
		$qb = $this->getEntityManager()->createQueryBuilder();

		$qb->select('ct')
			->from(FactionToPlayerCreditTransaction::class, 'ct')
			->where('ct.receiver = :faction')
			->orderBy('ct.createdAt', 'DESC')
			->setMaxResults(20)
			->setParameter('faction', $faction);

		return $qb->getQuery()->getResult();
	}
}
