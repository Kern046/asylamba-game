<?php

namespace App\Modules\Hermes\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

class Conversation
{
	public const CONVERSATION_BY_PAGE = 25;

	public const TY_USER = 1;
	public const TY_SYSTEM = 2;

	public function __construct(
		public Uuid $id,
		public \DateTimeImmutable $createdAt,
		public string|null $title = null,
		public \DateTimeImmutable|null $lastMessageAt = null,
		public int $messagesCount = 0,
		public int $type = self::TY_USER,
		public Collection $players = new ArrayCollection(),
	) {
			
	}

	public function getLastPage(): int
	{
		return ceil($this->messagesCount / ConversationMessage::MESSAGE_BY_PAGE);
	}
}
