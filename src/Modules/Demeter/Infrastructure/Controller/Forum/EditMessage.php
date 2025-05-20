<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Controller\Forum;

use App\Classes\Library\Parser;
use App\Modules\Demeter\Domain\Repository\Forum\ForumMessageRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class EditMessage extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ForumMessageRepositoryInterface $forumMessageRepository,
		Parser $parser,
		int $forumId,
		Uuid $topicId,
		Uuid $messageId,
	): Response {
		$message = $forumMessageRepository->get($messageId)
			?? throw $this->createNotFoundException();

		$content = $request->request->get('content')
			?? throw new BadRequestHttpException('Missing content');

		// TODO Replace with Voter
		if ($currentPlayer->id === $message->player->id || ($currentPlayer->isGovernmentMember() && 20 != $message->topic->forum)) {
			$parser->parseBigTag = true;

			$message->oContent = $content;
			$message->pContent = $parser->parse($content);
			$message->updatedAt = new \DateTimeImmutable();

			$forumMessageRepository->save($message);

			$this->addFlash('success', 'Message édité.');
		} else {
			throw $this->createAccessDeniedException('Vous ne pouvez pas éditer ce message.');
		}

		return $this->redirectToRoute('view_forum_topic', [
			'forumId' => $forumId,
			'topicId' => $topicId,
		]);
	}
}
