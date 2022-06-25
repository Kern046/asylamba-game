<?php

namespace App\Modules\Atlas\Model;

use Symfony\Component\Uid\Uuid;

class Ranking
{
	public function __construct(
		public Uuid $id,
		public bool $isPlayer,
		public bool $isFaction,
		public \DateTimeImmutable $createdAt,
	) {

	}
}
