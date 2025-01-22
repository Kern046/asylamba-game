<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Ship;

use App\Modules\Athena\Domain\Enum\DockType;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class CountShipTimeCost
{
	public function __construct(
		private BonusApplierInterface $bonusApplier,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function __invoke(int $identifier, DockType $dockType, int $quantity = 1): int
	{
		$time = $this->timeMode->isStandard()
			? ShipResource::getInfo($identifier, 'time') * $quantity
			: (pow($identifier, 2) + 5) * $quantity;

		$bonus = $this->bonusApplier->apply($time, $dockType->getSpeedBonusId());

		return intval(round($time - $bonus));
	}
}
