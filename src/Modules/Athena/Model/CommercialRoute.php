<?php

namespace App\Modules\Athena\Model;

use Symfony\Component\Uid\Uuid;

class CommercialRoute
{
	// CONSTANTS
	public const COEF_PRICE = 8000;
	public const COEF_INCOME_1 = 300;
	public const COEF_INCOME_2 = 17;

	public const PROPOSED = 0;
	public const ACTIVE = 1;
	public const STANDBY = 2;

	public function __construct(
		public Uuid $id,
		public OrbitalBase $originBase,
		public OrbitalBase $destinationBase,
		public string $imageLink,
		public int $distance,
		public int $price,
		public int $income,
		public \DateTimeImmutable $proposedAt,
		public \DateTimeImmutable|null $acceptedAt = null,
		public int $statement = self::PROPOSED,
	) {

	}

	public function isProposed(): bool
	{
		return self::PROPOSED === $this->statement;
	}

	public function isActive(): bool
	{
		return self::ACTIVE === $this->statement;
	}

	public function isInStandBy(): bool
	{
		return self::STANDBY === $this->statement;
	}
}
