<?php

namespace App\Modules\Zeus\Model;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use Symfony\Component\Uid\Uuid;

class PlayerToFactionCreditTransaction extends CreditTransaction
{
	public function __construct(
		public Player $sender,
		public Color $receiver,
		Uuid $id,
		int $amount,
		\DateTimeImmutable $createdAt,
		string|null $comment,
	) {
		parent::__construct($id, $amount, $createdAt, $comment);
	}

	public function getFormatedReceiverLink(): string
	{
		return '/embassy/faction-' . $this->receiver->id;
	}

	public function getFormatedReceiverName(): string
	{
		return ColorResource::getInfo($this->receiver->identifier, 'popularName');
	}

	public function getFormatedReceiverAvatar(): string
	{
		return 'color-' . $this->receiver->identifier;
	}

	public function getFormatedReceiverStatus(): string
	{
		return ColorResource::getInfo($this->receiver->identifier, 'government');
	}

	public function getFormatedReceiverColor(): Color
	{
		return $this->receiver;
	}
}
