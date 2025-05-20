<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Building;

use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class GetTimeCost
{
	public function __construct(
		private BonusApplierInterface $bonusApplier,
		private BuildingDataHandler $buildingDataHandler,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function __invoke(int $identifier, int $targetLevel): int
	{
		$time = $this->timeMode->isStandard()
			? $this->buildingDataHandler->getBuildingTimeCost($identifier, $targetLevel)
			: $targetLevel * 10;

		$bonus = $this->bonusApplier->apply($time, PlayerBonusId::GENERATOR_SPEED);

		return intval(round($time - $bonus));
	}
}
