<?php

namespace App\Modules\Demeter\Model\Election;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class Vote
{
	public function __construct(
		public Uuid $id,
		public Candidate $candidate,
		public Player $player,
		public bool $hasApproved,
		public \DateTimeImmutable $votedAt,
	) {
	}
}
