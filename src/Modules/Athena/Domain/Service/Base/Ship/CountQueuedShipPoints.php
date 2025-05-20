<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Service\Base\Ship;

use App\Modules\Athena\Model\ShipQueue;
use App\Modules\Athena\Resource\ShipResource;

readonly class CountQueuedShipPoints
{
	/**
	 * @param list<ShipQueue> $shipQueues
	 */
	public function __invoke(array $shipQueues): int
	{
		$inQueue = 0;

		foreach ($shipQueues as $shipQueue) {
			$inQueue += ShipResource::getInfo($shipQueue->shipNumber, 'pev') * $shipQueue->quantity;
		}

		return $inQueue;
	}
}
