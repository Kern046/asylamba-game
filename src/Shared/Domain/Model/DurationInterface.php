<?php

namespace App\Shared\Domain\Model;

interface DurationInterface
{
	public function getStartDate(): \DateTimeImmutable;

	public function getEndDate(): \DateTimeImmutable;
}
