<?php

declare(strict_types=1);

namespace App\Modules\Athena\Message\Base;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

readonly class BaseUpdateMessage implements AsyncMessage
{
	public function __construct(public Uuid $baseId)
	{
	}
}
