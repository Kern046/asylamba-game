<?php

use App\Classes\Exception\ErrorException;
use App\Classes\Library\Flashbag;
use App\Modules\Hermes\Model\ConversationUser;

$request = $this->getContainer()->get('app.request');
$session = $this->getContainer()->get(\App\Classes\Library\Session\SessionWrapper::class);
$conversationManager = $this->getContainer()->get(\App\Modules\Hermes\Manager\ConversationManager::class);

$conversation = $request->query->get('conversation');

if (false !== $conversation) {
	// vérifier que c'est l'utilisateur courant

	$S_CVM = $conversationManager->getCurrentSession();
	$conversationManager->newSession();
	$conversationManager->load(
		['c.id' => $conversation, 'cu.rPlayer' => $session->get('playerId')]
	);

	if (1 == $conversationManager->size()) {
		$conv = $conversationManager->get();
		$users = $conv->players;

		foreach ($users as $user) {
			if ($user->rPlayer == $session->get('playerId')) {
				if (ConversationUser::CS_DISPLAY == $user->convStatement) {
					$user->convStatement = ConversationUser::CS_ARCHIVED;
					$session->addFlashbag('La conversation a été archivée.', Flashbag::TYPE_SUCCESS);
				} else {
					$user->convStatement = ConversationUser::CS_DISPLAY;
					$session->addFlashbag('La conversation a été désarchivée.', Flashbag::TYPE_SUCCESS);
				}
				break;
			}
		}
	} else {
		throw new ErrorException('La conversation n\'existe pas ou ne vous appartient pas.');
	}

	$conversationManager->changeSession($S_CVM);
} else {
	throw new ErrorException('Informations manquantes pour quitter la conversation.');
}
