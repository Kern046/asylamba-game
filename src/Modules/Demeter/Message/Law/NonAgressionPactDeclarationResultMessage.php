<?php

namespace App\Modules\Demeter\Message\Law;

use App\Shared\Domain\Message\AsyncMessage;
use Symfony\Component\Uid\Uuid;

readonly class NonAgressionPactDeclarationResultMessage implements AsyncMessage
{
	public function __construct(private Uuid $lawId)
	{
	}

	public function getLawId(): Uuid
	{
		return $this->lawId;
	}
}
