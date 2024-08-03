<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base;

use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Manager\PlayerBonusManager;
use App\Modules\Zeus\Model\PlayerBonusId;

readonly class GetMaxStorage
{
	public function __construct(
		private BonusApplierInterface $bonusApplier,
		private OrbitalBaseHelper $orbitalBaseHelper,
		private PlayerBonusManager $playerBonusManager,
	) {
	}

	public function __invoke(OrbitalBase $base, bool $offLimits = false): int
	{
		$playerBonus = $this->playerBonusManager->getBonusByPlayer($base->player);
		$maxStorage = $this->orbitalBaseHelper->getBuildingInfo(
			OrbitalBaseResource::STORAGE,
			'level',
			$base->levelStorage,
			'storageSpace',
		);
		$maxStorage += intval(round($this->bonusApplier->apply($maxStorage, PlayerBonusId::REFINERY_STORAGE, $playerBonus)));

		if (true === $offLimits) {
			$maxStorage += OrbitalBase::EXTRA_STOCK;
		}

		return $maxStorage;
	}
}
