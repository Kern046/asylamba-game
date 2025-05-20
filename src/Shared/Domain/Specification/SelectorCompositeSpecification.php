<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Constraints\Compound;

abstract class SelectorCompositeSpecification extends Compound implements SelectorSpecification
{
	public function addMatchingCriteria(QueryBuilder $queryBuilder): void
	{
		foreach ($this->getConstraints([]) as $constraint) {
			if ($constraint instanceof SelectorSpecification) {
				$constraint->addMatchingCriteria($queryBuilder);
			}
		}
	}
}
