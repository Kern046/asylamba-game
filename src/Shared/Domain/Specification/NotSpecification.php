<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

class NotSpecification implements Specification
{
	public function __construct(private readonly Specification $specification)
	{
	}

	public function isSatisfiedBy($candidate): bool
	{
		return !$this->specification->isSatisfiedBy($candidate);
	}
}
