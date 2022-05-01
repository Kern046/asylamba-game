<?php

/**
 * CreditTransaction.
 *
 * @author Jacky Casas
 * @copyright Asylamba
 *
 * @update 09.02.15
 */

namespace App\Modules\Zeus\Model;

use App\Modules\Demeter\Resource\ColorResource;

class CreditTransaction
{
	public const TYP_PLAYER = 0;
	public const TYP_FACTION = 1;
	public const TYP_F_TO_P = 2; // faction to player

	public $id = 0;
	public $rSender = 0;
	public $type = 0; // 0 = player, 1 = faction
	public $rReceiver = 0;
	public $amount = 0;
	public $dTransaction = 0;
	public $comment = '';

	public $senderName = '';
	public $senderAvatar = '';
	public $senderStatus = '';
	public $senderColor = '';

	public $receiverName = '';
	public $receiverAvatar = '';
	public $receiverStatus = '';
	public $receiverColor = '';

	public function getFormatedReceiverLink()
	{
		return CreditTransaction::TYP_PLAYER == $this->type
			? '/embassy/player-'.$this->rReceiver
			: '/embassy/faction-'.$this->rReceiver;
	}

	public function getFormatedReceiverName()
	{
		return CreditTransaction::TYP_PLAYER == $this->type
			? $this->receiverName
			: ColorResource::getInfo($this->rReceiver, 'popularName');
	}

	public function getFormatedReceiverAvatar()
	{
		return CreditTransaction::TYP_PLAYER == $this->type
			? $this->receiverAvatar
			: 'color-'.$this->rReceiver;
	}

	public function getFormatedReceiverStatus()
	{
		if (CreditTransaction::TYP_PLAYER == $this->type) {
			$status = ColorResource::getInfo($this->receiverColor, 'status');

			return $status[$this->receiverStatus - 1];
		} else {
			return ColorResource::getInfo($this->rReceiver, 'government');
		}
	}

	public function getFormatedReceiverColor()
	{
		return CreditTransaction::TYP_PLAYER == $this->type
			? $this->receiverColor
			: $this->rReceiver;
	}

	public function getId()
	{
		return $this->id;
	}
}
