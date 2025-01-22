<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Enum;

use App\Modules\Ares\Model\Ship;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Zeus\Model\PlayerBonusId;

enum DockType: string
{
	case Manufacture = 'manufacture';
	case Shipyard = 'shipyard';

	public function getShipRange(): array
	{
		return match ($this) {
			self::Manufacture => range(Ship::TYPE_PEGASE, Ship::TYPE_MEDUSE),
			self::Shipyard => range(Ship::TYPE_GRIFFON, Ship::TYPE_PHENIX),
		};
	}

	public function getLevel(OrbitalBase $base): int
	{
		return match ($this) {
			self::Manufacture => $base->levelDock1,
			self::Shipyard => $base->levelDock2,
		};
	}

	public function getBuildingNumber(): int
	{
		return match ($this) {
			self::Manufacture => OrbitalBaseResource::DOCK1,
			self::Shipyard => OrbitalBaseResource::DOCK2,
		};
	}

	public function getSpeedBonusId(): int
	{
		return match ($this) {
			self::Manufacture => PlayerBonusId::DOCK1_SPEED,
			self::Shipyard => PlayerBonusId::DOCK2_SPEED,
		};
	}

	public function getIdentifier(): int
	{
		return match ($this) {
			self::Manufacture => 1,
			self::Shipyard => 2,
		};
	}
}
