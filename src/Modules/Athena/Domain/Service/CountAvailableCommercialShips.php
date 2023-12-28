<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service;

use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;

readonly class CountAvailableCommercialShips
{
	public function __construct(
		private CommercialShippingRepositoryInterface $commercialShippingRepository,
		private OrbitalBaseHelper $orbitalBaseHelper,
	) {
	}

	public function __invoke(OrbitalBase $base): int
	{
		// verif : have we enough commercialShips
		$totalShips = $this->orbitalBaseHelper->getBuildingInfo(
			OrbitalBaseResource::COMMERCIAL_PLATEFORME,
			'level',
			$base->levelCommercialPlateforme,
			'nbCommercialShip',
		);
		$usedShips = 0;

		// TODO transform this part into an optimized SQL query
		$commercialShippings = $this->commercialShippingRepository->getByBase($base);

		foreach ($commercialShippings as $commercialShipping) {
			if ($commercialShipping->originBase->id === $base->id) {
				$usedShips += $commercialShipping->shipQuantity;
			}
		}

		return $totalShips - $usedShips;
	}
}
