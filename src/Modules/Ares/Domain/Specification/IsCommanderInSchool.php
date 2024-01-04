<?php

declare(strict_types=1);

namespace App\Modules\Ares\Domain\Specification;

use App\Modules\Ares\Model\Commander;
use App\Shared\Domain\Specification\SelectorSpecification;
use App\Shared\Domain\Specification\Specification;
use Doctrine\ORM\QueryBuilder;

class IsCommanderInSchool implements Specification, SelectorSpecification
{
	/**
	 * @param Commander $candidate
	 */
	public function isSatisfiedBy($candidate): bool
	{
		return $candidate->isInSchool();
	}

	public function addMatchingCriteria(QueryBuilder $queryBuilder): void
	{
		$queryBuilder->andWhere('c.statement = :in_school_statement')
			->setParameter('in_school_statement', Commander::INSCHOOL);
	}
}
