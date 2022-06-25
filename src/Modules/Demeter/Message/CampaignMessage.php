<?php

namespace App\Modules\Demeter\Message;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

class CampaignMessage implements AsyncMessage
{
	public function __construct(private readonly Uuid $factionId)
	{
	}

	public function getFactionId(): Uuid
	{
		return $this->factionId;
	}
}
