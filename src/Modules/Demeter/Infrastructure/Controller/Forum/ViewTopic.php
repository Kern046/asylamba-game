<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Controller\Forum;

use App\Modules\Demeter\Application\Handler\Forum\UpsertTopicLastView;
use App\Modules\Demeter\Domain\Repository\Forum\ForumMessageRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Forum\ForumTopicRepositoryInterface;
use App\Modules\Demeter\Resource\ForumResources;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class ViewTopic extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ForumTopicRepositoryInterface $forumTopicRepository,
		ForumMessageRepositoryInterface $forumMessageRepository,
		UpsertTopicLastView $upsertTopicLastView,
		Uuid $topicId,
	): Response {
		$forumTopic = $forumTopicRepository->get($topicId)
			?? throw $this->createNotFoundException('Topic not found');

		$forumId = $forumTopic->forum;

		// TODO transform into Voter
		if (($forumId >= 10 && !$currentPlayer->isGovernmentMember()) || ($forumId >= 20 && !$currentPlayer->isGovernmentMember())) {
			throw $this->createAccessDeniedException('You do not have access to this forum');
		}
		// TODO Implement forum topic last view
		$upsertTopicLastView($forumTopic, $currentPlayer);

		$messages = $forumMessageRepository->getTopicMessages($forumTopic);
		$archivedMode = false;

		$topicsWithLastViews = ($forumId < 20)
			? $forumTopicRepository->getByForumAndFactionWithLastViews($forumId, $currentPlayer->faction, $currentPlayer, archived: $archivedMode)
			: $forumTopicRepository->getByForumWithLastViews($forumId, $currentPlayer, archived: $archivedMode);

		return $this->render('pages/demeter/faction/topic.html.twig', [
			'faction' => $currentPlayer->faction,
			'topic' => $forumTopic,
			'topics_with_last_views' => $topicsWithLastViews,
			'messages' => $messages,
			'forum_ids' => array_filter(
				array_map(
					fn (int $index) => ForumResources::getInfo($index, 'id'),
					range(1, ForumResources::size())
				),
				fn (int $forumId) => $this->canViewForum($currentPlayer, $forumId),
			),
		]);
	}

	// TODO Move to a dedicated Voter
	private function canViewForum(Player $player, int $forumId): bool
	{
		return $forumId < 10
			|| ($forumId >= 10 && $forumId < 20 && $player->isGovernmentMember())
			|| ($forumId >= 20 && $forumId < 30 && $player->isRuler());
	}
}
