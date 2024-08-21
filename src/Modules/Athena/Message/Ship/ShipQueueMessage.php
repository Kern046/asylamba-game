<?php

namespace App\Modules\Athena\Message\Ship;

use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use Symfony\Component\Uid\Uuid;

class ShipQueueMessage implements AsyncHighPriorityMessage
{
	public function __construct(private readonly Uuid $shipQueueId)
	{
	}

	public function getShipQueueId(): Uuid
	{
		return $this->shipQueueId;
	}
}
