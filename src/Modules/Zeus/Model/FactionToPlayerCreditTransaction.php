<?php

namespace App\Modules\Zeus\Model;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use Symfony\Component\Uid\Uuid;

class FactionToPlayerCreditTransaction extends CreditTransaction
{
	public function __construct(
		public Color $sender,
		public Player $receiver,
		Uuid $id,
		int $amount,
		\DateTimeImmutable $createdAt,
		string|null $comment,
	) {
		parent::__construct($id, $amount, $createdAt, $comment);
	}

	public function getFormatedReceiverLink(): string
	{
		return '/embassy/player-' . $this->receiver->id;
	}

	public function getFormatedReceiverName(): string
	{
		return $this->receiver->name;
	}

	public function getFormatedReceiverAvatar(): string
	{
		return $this->receiver->avatar;
	}

	public function getFormatedReceiverStatus(): string
	{
		$status = ColorResource::getInfo($this->receiver->color->id, 'status');

		return $status[$this->receiver->status - 1];
	}

	public function getFormatedReceiverColor(): Color
	{
		return $this->receiver->color;
	}
}
