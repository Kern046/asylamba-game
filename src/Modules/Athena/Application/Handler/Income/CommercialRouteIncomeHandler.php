<?php

namespace App\Modules\Athena\Application\Handler\Income;

use App\Modules\Athena\Domain\DTO\CommercialRouteIncome;
use App\Modules\Athena\Domain\Repository\CommercialRouteRepositoryInterface;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\PlayerBonusId;

readonly class CommercialRouteIncomeHandler
{
	public function __construct(
		private BonusApplierInterface $bonusApplier,
		private CommercialRouteRepositoryInterface $commercialRouteRepository,
	) {
	}

	public function getCommercialRouteIncome(OrbitalBase $base): CommercialRouteIncome
	{
		$initialValue = $this->commercialRouteRepository->getBaseIncome($base);

		$bonus = intval($this->bonusApplier->apply($initialValue, PlayerBonusId::COMMERCIAL_INCOME));

		return new CommercialRouteIncome(
			initial: $initialValue,
			bonus: $bonus,
			total: $initialValue + $bonus,
		);
	}
}
