<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\DTO;

readonly class PopulationTax
{
	public function __construct(
		public int $initial,
		public int $bonus,
	) {
	}

	public function getTotal(): int
	{
		return $this->initial + $this->bonus;
	}
}
