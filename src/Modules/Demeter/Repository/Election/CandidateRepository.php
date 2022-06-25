<?php

namespace App\Modules\Demeter\Repository\Election;

use App\Modules\Demeter\Model\Election\Candidate;
use App\Modules\Demeter\Model\Election\Election;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<Candidate>
 */
class CandidateRepository extends DoctrineRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Candidate::class);
	}

	public function get(Uuid $id): Candidate|null
	{
		return $this->find($id);
	}

	public function getByElectionAndPlayer(Election $election, Player $player): Candidate|null
	{
		return $this->findOneBy([
			'player' => $player,
			'election' => $election,
		]);
	}

	public function getByElection(Election $election): array
	{
		return $this->findBy([
			'election' => $election,
		]);
	}
}
