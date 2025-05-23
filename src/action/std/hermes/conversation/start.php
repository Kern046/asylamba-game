<?php

use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Classes\Library\Flashbag;
use App\Classes\Library\Utils;
use App\Modules\Hermes\Model\Conversation;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Hermes\Model\ConversationUser;
use App\Modules\Zeus\Model\Player;

$database = $this->getContainer()->get(\App\Classes\Database\Database::class);
$playerManager = $this->getContainer()->get(\App\Modules\Zeus\Manager\PlayerManager::class);
$conversationManager = $this->getContainer()->get(\App\Modules\Hermes\Manager\ConversationManager::class);
$conversationUserManager = $this->getContainer()->get(\App\Modules\Hermes\Manager\ConversationUserManager::class);
$conversationMessageManager = $this->getContainer()->get(\App\Modules\Hermes\Manager\ConversationMessageManager::class);
$response = $this->getContainer()->get('app.response');
$request = $this->getContainer()->get('app.request');
$parser = $this->getContainer()->get(\App\Classes\Library\Parser::class);

$recipients = $request->request->get('recipients');
$content = $request->request->get('content');

$content = $parser->parse($content);

if (!empty($recipients) && !empty($content)) {
	if (strlen((string) $content) < 10000) {
		// traitement des utilisateurs multiples
		$recipients = explode(',', (string) $recipients);
		$plId = $session->get('playerId');
		$recipients = array_filter($recipients, fn($e) => $e == $plId ? false : true);
		$recipients[] = 0;

		if (count($recipients) <= ConversationUser::MAX_USERS) {
			// chargement des utilisateurs
			$players = $playerManager->getByIdsAndStatements($recipients, [Player::ACTIVE, Player::INACTIVE, Player::HOLIDAY]);

			if (count($players) >= 1) {
				// création de la date précédente
				$readingDate = date('Y-m-d H:i:s', (strtotime(Utils::now()) - 20));

				// créer la conversation
				$conv = new Conversation();

				$conv->messages = 1;
				$conv->type = Conversation::TY_USER;
				$conv->dCreation = Utils::now();
				$conv->dLastMessage = Utils::now();

				$conversationManager->add($conv);

				// créer le user créateur de la conversation
				$user = new ConversationUser();

				$user->rConversation = $conv->id;
				$user->rPlayer = $session->get('playerId');
				$user->convPlayerStatement = ConversationUser::US_ADMIN;
				$user->convStatement = ConversationUser::CS_DISPLAY;
				$user->dLastView = Utils::now();

				$conversationUserManager->add($user);

				// créer la liste des users
				foreach ($players as $player) {
					$user = new ConversationUser();

					$user->rConversation = $conv->id;
					$user->rPlayer = $player->id;
					$user->convPlayerStatement = ConversationUser::US_STANDARD;
					$user->convStatement = ConversationUser::CS_DISPLAY;
					$user->dLastView = $readingDate;

					$conversationUserManager->add($user);
				}

				// créer le premier message
				$message = new ConversationMessage();

				$message->rConversation = $conv->id;
				$message->rPlayer = $session->get('playerId');
				$message->type = ConversationMessage::TY_STD;
				$message->content = $content;
				$message->dCreation = Utils::now();
				$message->dLastModification = null;

				$conversationMessageManager->add($message);

				if (true === $this->getContainer()->getParameter('data_analysis')) {
					$qr = $database->prepare(
						'INSERT INTO 
						DA_SocialRelation(`from`, `to`, `type`, `message`, dAction)
						VALUES(?, ?, ?, ?, ?)'
					);
					$qr->execute([$session->get('playerId'), $players[0]->getId(), 2, $content, Utils::now()]);
				}

				$session->addFlashbag('La conversation a été créée.', Flashbag::TYPE_SUCCESS);
				$response->redirect('message/conversation-'.$conv->id);
			} else {
				throw new ErrorException('Le joueur n\'est pas joignable.');
			}
		} else {
			throw new ErrorException('Nombre maximum de joueur atteint.');
		}
	} else {
		throw new ErrorException('Le message est trop long.');
	}
} else {
	throw new FormException('Informations manquantes pour démarrer une nouvelle conversation.');
}
