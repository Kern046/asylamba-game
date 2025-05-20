<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Infrastructure\Repository;

use App\Modules\Hermes\Domain\Repository\ConversationMessageRepositoryInterface;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConversationMessageRepository extends DoctrineRepository implements ConversationMessageRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, ConversationMessage::class);
	}

	public function getConversationMessages(Conversation $conversation, int $messagesPage): array
	{
		return $this->findBy(
			criteria: [
				'conversation' => $conversation,
			],
			orderBy: [
				'createdAt' => 'DESC',
			],
			limit: ConversationMessage::MESSAGE_BY_PAGE,
			offset: ($messagesPage - 1) * ConversationMessage::MESSAGE_BY_PAGE,
		);
	}
}
