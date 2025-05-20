<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service;

use App\Classes\Library\Game;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Athena\Resource\ShipResource;

class CountNeededCommercialShips
{
	public function __invoke(int $transactionType, int $quantity = 1, int|string $identifier = 0): int
	{
		return match ($transactionType) {
			// 1000 ressources => 1 commercialShip
			Transaction::TYP_RESOURCE => intval(ceil($quantity / 1000)),
			// 1 PEV => 1 commercialShip
			Transaction::TYP_SHIP => (ShipResource::isAShip(intval($identifier)) and $quantity > 0)
				? $quantity * ShipResource::getInfo(intval($identifier), 'pev')
				: throw new \LogicException('Invalid ship or quantity'),
			// 1 commander => 1 commercialShip
			Transaction::TYP_COMMANDER => 1,
			default => throw new \LogicException('Unknown transaction type'),
		};
	}
}
