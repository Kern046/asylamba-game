<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Message;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

readonly class BallotMessage implements AsyncMessage
{
	public function __construct(public Uuid $factionId)
	{
	}
}
