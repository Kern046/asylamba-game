<?php

namespace App\Shared\Domain\Specification;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;

interface SelectorSpecification
{
	public function addMatchingCriteria(QueryBuilder $queryBuilder): void;
}
