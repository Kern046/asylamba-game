<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Repository\Law;

use App\Modules\Demeter\Domain\Repository\Law\VoteLawRepositoryInterface;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Model\Law\VoteLaw;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;

class VoteLawRepository extends DoctrineRepository implements VoteLawRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, VoteLaw::class);
	}

	public function getLawVotes(Law $law): array
	{
		return $this->findBy([
			'law' => $law,
		]);
	}

	public function hasVoted(Player $player, Law $law): bool
	{
		return $this->count([
			'law' => $law,
			'player' => $player,
		]) > 0;
	}
}
