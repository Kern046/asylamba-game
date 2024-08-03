<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Application\Handler\Forum;

use App\Modules\Demeter\Domain\Repository\Forum\ForumTopicLastViewRepositoryInterface;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Demeter\Model\Forum\ForumTopicLastView;
use App\Modules\Zeus\Model\Player;
use Psr\Clock\ClockInterface;

readonly class UpsertTopicLastView
{
	public function __construct(
		private ClockInterface $clock,
		private ForumTopicLastViewRepositoryInterface $forumTopicLastViewRepository,
	) {
	}

	public function __invoke(ForumTopic $forumTopic, Player $player): ForumTopicLastView
	{
		if (null !== ($topicLastView = $this->forumTopicLastViewRepository->getByTopicAndPlayer($forumTopic, $player))) {
			$topicLastView->viewedAt = $this->clock->now();

			$this->forumTopicLastViewRepository->save($topicLastView);

			return $topicLastView;
		}
		$topicLastView = new ForumTopicLastView(
			player: $player,
			forumTopic: $forumTopic,
			viewedAt: $this->clock->now(),
		);

		$this->forumTopicLastViewRepository->save($topicLastView);

		return $topicLastView;
	}
}
