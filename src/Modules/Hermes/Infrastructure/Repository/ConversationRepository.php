<?php

namespace App\Modules\Hermes\Infrastructure\Repository;

use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<Conversation>
 */
class ConversationRepository extends DoctrineRepository implements ConversationRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Conversation::class);
	}

	public function getOneByPlayer(Player $player): Conversation
	{
		$qb = $this->createQueryBuilder('c');

		$qb
			->leftJoin('c.players', 'cu')
			->where('cu.player = :player')
			->setParameter('player', $player);

		return $qb->getQuery()->getSingleResult();
	}

	public function countPlayerConversations(Player $player): int
	{
		$qb = $this->createQueryBuilder('c');

		$qb
			->select('COUNT(c.id)')
			->leftJoin('c.players', 'cu')
			->where('cu.player = :player')
			->andWhere('cu.lastViewedAt < c.lastMessageAt')
			->setParameter('player', $player);

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getPlayerConversations(Player $player, int $mode): array
	{
		$qb = $this->createQueryBuilder('c');

		$qb
			->select('COUNT(c.id)')
			->leftJoin('c.players', 'cu')
			->where('cu.player = :player')
			->andWhere('cu.conversationStatus = :mode')
			->orderBy('c.lastMessageAt', 'DESC')
			->setMaxResults(Conversation::CONVERSATION_BY_PAGE)
			->setParameter('player', $player)
			->setParameter('mode', $mode);

		return $qb->getQuery()->getResult();
	}
}
