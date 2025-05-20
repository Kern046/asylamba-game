<?php

namespace App\Modules\Ares\Message;

use App\Shared\Domain\Message\AsyncHighPriorityMessage;
use Symfony\Component\Uid\Uuid;

class CommanderTravelMessage implements AsyncHighPriorityMessage
{
	public function __construct(private readonly Uuid $commanderId)
	{
	}

	public function getCommanderId(): Uuid
	{
		return $this->commanderId;
	}
}
