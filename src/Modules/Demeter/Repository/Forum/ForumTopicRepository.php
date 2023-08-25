<?php

namespace App\Modules\Demeter\Repository\Forum;

use App\Modules\Demeter\Domain\Repository\Forum\ForumTopicRepositoryInterface;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Shared\Infrastructure\Repository\Doctrine\DoctrineRepository;
use App\Modules\Zeus\Model\Player;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineRepository<ForumTopic>
 */
class ForumTopicRepository extends DoctrineRepository implements ForumTopicRepositoryInterface
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, ForumTopic::class);
	}

	public function getByForumAndPlayer(int $forum, Player $player): ForumTopic|null
	{
		return $this->findOneBy([
			'forum' => $forum,
			'player' => $player,
		], [
			'createdAt' => 'DESC',
		]);
	}
}
