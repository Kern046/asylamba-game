<?php

namespace App\Modules\Ares\Message;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

class CommanderTravelMessage implements AsyncMessage
{
	public function __construct(private readonly Uuid $commanderId)
	{
	}

	public function getCommanderId(): Uuid
	{
		return $this->commanderId;
	}
}
