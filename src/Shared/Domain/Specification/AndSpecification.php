<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

class AndSpecification implements Specification
{
	/**
	 * @var list<Specification>
	 */
	protected readonly array $specifications;

	public function __construct(Specification ...$specifications)
	{
		$this->specifications = $specifications;
	}

	public function isSatisfiedBy($candidate): bool
	{
		foreach ($this->specifications as $specification) {
			if (!$specification->isSatisfiedBy($candidate)) {
				return false;
			}
		}

		return true;
	}
}
