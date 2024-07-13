<?php

namespace App\Modules\Hermes\Domain\Repository;

use App\Modules\Hermes\Model\Conversation;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

interface ConversationRepositoryInterface extends EntityRepositoryInterface
{
	public function getOneByPlayer(Player $player): Conversation;

	public function getOne(Uuid $id): Conversation|null;

	public function countPlayerConversations(Player $player): int;

	/**
	 * @return list<Conversation>
	 */
	public function getPlayerConversations(Player $player, int $mode, int $page = 1): array;
}
