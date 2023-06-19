<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Infrastructure\Validator;

use App\Modules\Demeter\Model\Color;
use App\Shared\Domain\Specification\SelectorSpecification;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Constraint;

class IsFromFaction extends Constraint implements SelectorSpecification
{
	public function __construct(
		private readonly Color $faction,
		array $groups = null,
		mixed $payload = null
	) {
		parent::__construct([], $groups, $payload);
	}

	public function addMatchingCriteria(QueryBuilder $queryBuilder): void
	{
		$queryBuilder->andWhere($queryBuilder->expr()->eq('p.faction', ':faction'))
			->setParameter('faction', $this->faction);
	}
}
