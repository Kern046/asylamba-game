<?php

namespace App\Modules\Zeus\Domain\Message;

use App\Shared\Domain\Message\AsyncMessage;

readonly class PlayerCreditUpdateMessage implements AsyncMessage
{
	public function __construct(private int $playerId)
	{

	}

	public function getPlayerId(): int
	{
		return $this->playerId;
	}
}
