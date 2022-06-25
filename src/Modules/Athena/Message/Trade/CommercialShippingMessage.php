<?php

namespace App\Modules\Athena\Message\Trade;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

class CommercialShippingMessage implements AsyncMessage
{
	public function __construct(private readonly Uuid $commercialShippingId)
	{
	}

	public function getCommercialShippingId(): Uuid
	{
		return $this->commercialShippingId;
	}
}
