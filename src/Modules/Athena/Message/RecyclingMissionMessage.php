<?php

namespace App\Modules\Athena\Message;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

class RecyclingMissionMessage implements AsyncMessage
{
	public function __construct(private readonly Uuid $recyclingMissionId)
	{
	}

	public function getRecyclingMissionId(): Uuid
	{
		return $this->recyclingMissionId;
	}
}
