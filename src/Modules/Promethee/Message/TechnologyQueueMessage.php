<?php

namespace App\Modules\Promethee\Message;

use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use Symfony\Component\Uid\Uuid;

class TechnologyQueueMessage implements AsyncHighPriorityMessage
{
	public function __construct(private readonly Uuid $technologyQueueId)
	{
	}

	public function getTechnologyQueueId(): Uuid
	{
		return $this->technologyQueueId;
	}
}
