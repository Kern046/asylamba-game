<?php

namespace App\Modules\Hermes\Infrastructure\Controller\Conversation;

use App\Modules\Hermes\Domain\Repository\ConversationRepositoryInterface;
use App\Modules\Hermes\Model\ConversationUser;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ViewList extends AbstractController
{
	#[Route(
		path: '/messages',
		name: 'list_conversations',
		methods: Request::METHOD_GET
	)]
	public function __invoke(
		Request $request,
		ConversationRepositoryInterface $conversationRepository,
		Player $currentPlayer,
	): Response {
		$mode = (ConversationUser::CS_ARCHIVED === $request->query->getInt('mode'))
			? ConversationUser::CS_ARCHIVED
			: ConversationUser::CS_DISPLAY;

		return $this->render('pages/hermes/conversation/list.html.twig', [
			'conversations' => $conversationRepository->getPlayerConversations($currentPlayer, $mode),
			'mode' => $mode,
		]);
	}
}
