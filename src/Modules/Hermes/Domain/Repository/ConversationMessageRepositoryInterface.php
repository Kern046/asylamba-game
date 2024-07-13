<?php

namespace App\Modules\Hermes\Domain\Repository;

use App\Modules\Hermes\Model\Conversation;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

/**
 * @extends EntityRepositoryInterface<ConversationMessage>
 */
interface ConversationMessageRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return list<ConversationMessage>
	 */
	public function getConversationMessages(Conversation $conversation, int $messagesPage): array;
}
