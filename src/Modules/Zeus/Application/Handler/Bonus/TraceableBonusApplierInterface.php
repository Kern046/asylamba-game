<?php

namespace App\Modules\Zeus\Application\Handler\Bonus;

class TraceableBonusApplierInterface extends AbstractBonusApplier
{
	private array $tracedBonuses = [];

	public function apply()
	{
		// TODO: Implement apply() method.
	}

	public function getTracedBonuses(): array
	{
		return $this->tracedBonuses;
	}
}
