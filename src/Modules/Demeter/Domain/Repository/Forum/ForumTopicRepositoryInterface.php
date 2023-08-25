<?php

namespace App\Modules\Demeter\Domain\Repository\Forum;

use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;
use App\Modules\Zeus\Model\Player;

/**
 * @extends EntityRepositoryInterface<ForumTopic>
 */
interface ForumTopicRepositoryInterface extends EntityRepositoryInterface
{
	public function getByForumAndPlayer(int $forum, Player $player): ForumTopic|null;
}
