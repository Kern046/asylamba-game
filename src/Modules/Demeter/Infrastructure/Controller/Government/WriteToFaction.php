<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government;

use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Classes\Library\Parser;
use App\Classes\Library\Utils;
use App\Modules\Hermes\Manager\ConversationManager;
use App\Modules\Hermes\Manager\ConversationMessageManager;
use App\Modules\Hermes\Model\ConversationMessage;
use App\Modules\Hermes\Model\ConversationUser;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WriteToFaction extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		PlayerManager $playerManager,
		Parser $parser,
		ConversationManager $conversationManager,
		ConversationMessageManager $conversationMessageManager,
	): Response {
		// @TODO fix empty check
		$content = $parser->parse($request->request->get('message'));

		if ($content !== null) {
			if ($currentPlayer->isGovernmentMember()) {
				if ($content !== '' && strlen($content) < 25000) {
					if (($factionAccount = $playerManager->getFactionAccount($currentPlayer->rColor)) !== null) {
						$S_CVM = $conversationManager->getCurrentSession();
						$conversationManager->newSession();
						$conversationManager->load(
							['cu.rPlayer' => $factionAccount->id]
						);

						if ($conversationManager->size() == 1) {
							$conv = $conversationManager->get();

							$conv->messages++;
							$conv->dLastMessage = Utils::now();

							# désarchiver tout les users
							$users = $conv->players;
							foreach ($users as $user) {
								$user->convStatement = ConversationUser::CS_DISPLAY;
							}

							# création du message
							$message = new ConversationMessage();

							$message->rConversation = $conv->id;
							$message->rPlayer = $currentPlayer->id;
							$message->type = ConversationMessage::TY_STD;
							$message->content = $content;
							$message->dCreation = Utils::now();
							$message->dLastModification = NULL;

							$conversationMessageManager->add($message);

						} else {
							throw new ErrorException('La conversation n\'existe pas ou ne vous appartient pas.');
						}

						$conversationManager->changeSession($S_CVM);
					}
				} else {
					throw new FormException('Le message est vide ou trop long');
				}
			} else {
				throw new FormException('Vizs n\'avez pas les droits pour poster un message officiel');
			}
		} else {
			throw new FormException('Pas assez d\'informations pour écrire un message officiel');
		}

		return $this->redirect($request->headers->get('referer'));
	}
}
