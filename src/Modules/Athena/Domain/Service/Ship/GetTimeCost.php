<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Ship;

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

	public function __invoke(int $identifier, int $dockType, int $quantity): int
	{
		$time = $this->timeMode->isStandard()
			? ShipResource::getInfo($identifier, 'time') * $quantity
			: (pow($identifier, 2) + 5) * $quantity;

		$bonus = $this->bonusApplier->apply($time, match ($dockType) {
			1 => PlayerBonusId::DOCK1_SPEED,
			2 => PlayerBonusId::DOCK2_SPEED,
			3 => PlayerBonusId::DOCK3_SPEED,
			default => throw new \LogicException('Invalid Dock ID'),
		});

		return intval(round($time - $bonus));
	}
}
