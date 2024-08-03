<?php

namespace App\Modules\Zeus\Model;

interface CreditHolderInterface
{
	public function setCredits(int $credit): static;

	public function getCredits(): int;

	public function canAfford(int $amount): bool;
}
