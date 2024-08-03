<?php

namespace App\Modules\Zeus\Repository;

use App\Modules\Demeter\Model\Color;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Domain\Repository\CreditTransactionRepositoryInterface;
use App\Modules\Zeus\Model\CreditHolderInterface;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;
use Doctrine\Common\Util\ClassUtils;
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
		return $this->findBy(
			match (ClassUtils::getClass($sender)) {
				Color::class => ['factionSender' => $sender],
				Player::class => ['playerSender' => $sender],
			},
			[
				'createdAt' => 'DESC',
			],
			20,
		);
	}

	public function getAllByPlayerReceiver(Player $player): array
	{
		return $this->findBy([
			'playerReceiver' => $player,
		], [
			'createdAt' => 'DESC',
		], 20);
	}

	public function getAllByFactionReceiverFromMembers(Color $faction): array
	{
		return $this->findBy([
			'factionReceiver' => $faction,
			'factionSender' => null,
		], [
			'createdAt' => 'DESC',
		], 20);
	}

	public function getAllByFactionReceiverFromFactions(Color $faction): array
	{
		return $this->findBy([
			'factionReceiver' => $faction,
			'playerSender' => null,
		], [
			'createdAt' => 'DESC',
		], 20);
	}
}
