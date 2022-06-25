<?php

namespace App\Modules\Athena\Message\Building;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

class BuildingQueueMessage implements AsyncMessage
{
	public function __construct(private readonly Uuid $buildingQueueId)
	{
	}

	public function getBuildingQueueId(): Uuid
	{
		return $this->buildingQueueId;
	}
}
