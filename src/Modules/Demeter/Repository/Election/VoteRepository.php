<?php

namespace App\Modules\Demeter\Repository\Election;

use App\Modules\Demeter\Domain\Repository\Election\VoteRepositoryInterface;
use App\Modules\Demeter\Model\Election\Election;
use App\Modules\Demeter\Model\Election\Vote;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<Vote>
 */
class VoteRepository extends DoctrineRepository implements VoteRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Vote::class);
	}

	public function getPlayerVote(Player $player, Election $election): Vote|null
	{
		$qb = $this->createQueryBuilder('v');

		$qb->join('v.candidate', 'c')
			->where('c.election = :election')
			->andWhere('c.player = :player')
			->setParameter('election', $election)
			->setParameter('player', $player);

		return $qb->getQuery()->getOneOrNullResult();
	}

	public function getElectionVotes(Election $election): array
	{
		$qb = $this->createQueryBuilder('v');

		$qb->join('v.candidate', 'c')
			->where('c.election = :election')
			->setParameter('election', $election);

		return $qb->getQuery()->getResult();
	}
}
