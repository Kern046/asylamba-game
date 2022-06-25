<?php

namespace App\Modules\Promethee\Message;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

class TechnologyQueueMessage implements AsyncMessage
{
	public function __construct(private readonly Uuid $technologyQueueId)
	{
	}

	public function getTechnologyQueueId(): Uuid
	{
		return $this->technologyQueueId;
	}
}
