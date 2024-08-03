<?php

namespace App\Shared\Domain\Model;

interface QueueableInterface extends DurationInterface
{
	public function getStartDate(): \DateTimeImmutable;

	public function getEndDate(): \DateTimeImmutable;

	public function getResourceIdentifier(): int;
}
