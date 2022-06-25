<?php

namespace App\Shared\Domain\Model;

interface QueueableInterface extends DurationInterface
{
	public function getResourceIdentifier(): int;
}
