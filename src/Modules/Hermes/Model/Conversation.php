<?php

namespace App\Modules\Hermes\Model;

class Conversation
{
	public const CONVERSATION_BY_PAGE = 25;

	public const TY_USER = 1;
	public const TY_SYSTEM = 2;

	public $id = 0;
	public $title = null;
	public $messages = 0;
	public $type = 1;
	public $dCreation = '';
	public $dLastMessage = null;

	public $players = [];

	public function getId()
	{
		return $this->id;
	}

	public function getLastPage()
	{
		return ceil($this->messages / ConversationMessage::MESSAGE_BY_PAGE);
	}
}
