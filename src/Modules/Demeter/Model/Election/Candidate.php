<?php

namespace App\Modules\Demeter\Model\Election;

use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class Candidate
{
	public function __construct(
		public Uuid $id,
		public Election $election,
		public Player $player,
		public Player|null $chiefChoice,
		public Player|null $treasurerChoice,
		public Player|null $warlordChoice,
		public Player|null $ministerChoice,
		public string $program,
		public \DateTimeImmutable $createdAt,
	) {
	}
}
