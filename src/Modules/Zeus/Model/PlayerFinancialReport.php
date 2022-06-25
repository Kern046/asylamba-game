<?php

namespace App\Modules\Zeus\Model;

use Symfony\Component\Uid\Uuid;

class PlayerFinancialReport
{
	public function __construct(
		public readonly Uuid $id,
		public readonly Player $player,
		public readonly \DateTimeImmutable $createdAt,
		public readonly int $initialWallet,
		public int $populationTaxes = 0,
		public int $commercialRoutesIncome = 0,
		public int $factionTaxes = 0,
		public int $antiSpyInvestments = 0,
		public int $universityInvestments = 0,
		public int $schoolInvestments = 0,
		public int $commandersWages = 0,
		public int $shipsCost = 0,
	) {
	}

	public function getTotalIncome(): int
	{
		return $this->populationTaxes + $this->commercialRoutesIncome;
	}

	public function getTotalLosses(): int
	{
		return $this->factionTaxes
			+ $this->antiSpyInvestments
			+ $this->universityInvestments
			+ $this->commandersWages
			+ $this->shipsCost;
	}

	public function getNewWallet(): int
	{
		return $this->initialWallet + $this->getTotalIncome() - $this->getTotalLosses();
	}

	public function canAfford(int $amount): bool
	{
		return $this->getNewWallet() >= $amount;
	}
}
