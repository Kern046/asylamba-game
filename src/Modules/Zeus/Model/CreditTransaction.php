<?php

namespace App\Modules\Zeus\Model;

use App\Modules\Demeter\Model\Color;
use Symfony\Component\Uid\Uuid;

abstract class CreditTransaction
{
	public function __construct(
		public Uuid $id,
		public int $amount,
		public \DateTimeImmutable $createdAt,
		public string|null $comment,
	) {
			
	}

	abstract public function getFormatedReceiverLink(): string;

	abstract public function getFormatedReceiverName(): string;

	abstract public function getFormatedReceiverAvatar(): string;

	abstract public function getFormatedReceiverStatus(): string;

	abstract public function getFormatedReceiverColor(): Color;
}
