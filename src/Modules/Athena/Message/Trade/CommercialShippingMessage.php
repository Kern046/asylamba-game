<?php

namespace App\Modules\Athena\Message\Trade;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

readonly class CommercialShippingMessage implements AsyncMessage
{
	public function __construct(private Uuid $commercialShippingId)
	{
	}

	public function getCommercialShippingId(): Uuid
	{
		return $this->commercialShippingId;
	}
}
