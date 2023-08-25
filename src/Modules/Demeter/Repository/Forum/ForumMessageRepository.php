<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Repository\Forum;

use App\Modules\Demeter\Domain\Repository\Forum\ForumMessageRepositoryInterface;
use App\Modules\Demeter\Model\Forum\ForumMessage;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<ForumMessage>
 */
class ForumMessageRepository extends DoctrineRepository implements ForumMessageRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, ForumMessage::class);
	}

	public function getTopicMessages(ForumTopic $topic): array
	{
		return $this->findBy([
			'topic' => $topic,
		], [
			'createdAt' => 'DESC',
		]);
	}
}
