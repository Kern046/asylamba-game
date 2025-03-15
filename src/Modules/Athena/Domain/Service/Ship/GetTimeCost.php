<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Ship;

use App\Modules\Athena\Domain\Model\DockType;
use App\Modules\Athena\Domain\Model\ShipType;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Shared\Domain\Server\TimeMode;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class GetTimeCost
{
	public function __construct(
		private BonusApplierInterface $bonusApplier,
		#[Autowire('%server_time_mode%')]
		private TimeMode $timeMode,
	) {
	}

	public function __invoke(ShipType $shipType, int $quantity): int
	{
		$time = $this->timeMode->isStandard()
			? ShipResource::getInfo($shipType, 'time') * $quantity
			: (pow($shipType->getIdentifier(), 2) + 5) * $quantity;

		$bonus = $this->bonusApplier->apply($time, match ($shipType->getDockType()) {
			DockType::Factory => PlayerBonusId::DOCK1_SPEED,
			DockType::Shipyard => PlayerBonusId::DOCK2_SPEED,
		});

		return intval(round($time - $bonus));
	}
}
