<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Controller\Forum;

use App\Classes\Library\Parser;
use App\Modules\Demeter\Domain\Repository\Forum\ForumMessageRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Forum\ForumTopicRepositoryInterface;
use App\Modules\Demeter\Model\Forum\ForumMessage;
use App\Modules\Demeter\Model\Forum\ForumTopic;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Resource\TutorialResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class CreateTopic extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ForumTopicRepositoryInterface $forumTopicRepository,
		ForumMessageRepositoryInterface $forumMessageRepository,
		PlayerRepositoryInterface $playerRepository,
		Parser $parser,
		int $forumId,
	): Response {
		$title = $request->request->get('title')
			?? throw new BadRequestHttpException('Missing title');
		$content = $request->request->get('content')
			?? throw new BadRequestHttpException('Missing content');

		$topic = new ForumTopic(
			id: Uuid::v4(),
			title: $title,
			player: $currentPlayer,
			forum: $forumId,
			messagesCount: 1,
			faction: $currentPlayer->faction,
			createdAt: new \DateTimeImmutable(),
			lastContributedAt: new \DateTimeImmutable(),
		);

		$forumTopicRepository->save($topic);

		$parser->parseBigTag = true;

		$pContent = $parser->parse($content);

		$message = new ForumMessage(
			id: Uuid::v4(),
			player: $currentPlayer,
			topic: $topic,
			oContent: $content,
			pContent: $pContent,
			statement: ForumMessage::PUBLISHED,
			createdAt: new \DateTimeImmutable(),
			updatedAt: new \DateTimeImmutable(),
		);

		$forumMessageRepository->save($message);

		// tutorial
		// TODO Move it to an event
		if (!$currentPlayer->stepDone && TutorialResource::FACTION_FORUM === $currentPlayer->stepTutorial) {
			$currentPlayer->stepDone = true;

			$playerRepository->save($currentPlayer);
		}

		$this->addFlash('success', 'Topic créé.');

		return $this->redirectToRoute('view_forum_topic', [
			'forumId' => $forumId,
			'topicId' => $topic->id->toRfc4122(),
		]);
	}
}
