<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Domain\Repository\Forum;

use App\Modules\Demeter\Model\Forum\ForumMessage;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Shared\Domain\Repository\EntityRepositoryInterface;

/**
 * @extends EntityRepositoryInterface<ForumMessage>
 */
interface ForumMessageRepositoryInterface extends EntityRepositoryInterface
{
	/**
	 * @return list<ForumMessage>
	 */
	public function getTopicMessages(ForumTopic $topic): array;
}
