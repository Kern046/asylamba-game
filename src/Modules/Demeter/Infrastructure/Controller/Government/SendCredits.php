<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Classes\Library\Format;
use App\Classes\Library\Parser;
use App\Classes\Library\Utils;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Manager\CreditTransactionManager;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SendCredits extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		Parser $parser,
		ColorManager $colorManager,
		PlayerManager $playerManager,
		CreditTransactionManager $creditTransactionManager,
		NotificationManager $notificationManager,
		EntityManager $entityManager,
	): Response {

		$name = $request->request->get('name');
		$quantity = $request->request->get('quantity');
		$text = $request->request->get('text');

		$name = $parser->protect($name);
		$text = $parser->parse($text);

		if ($name !== null AND $quantity !== null) {
			if ($currentPlayer->getStatus() == Player::TREASURER) {
				if (strlen($text) < 500) {
					$credit = intval($quantity);

					if ($credit > 0) {
						if (($faction = $colorManager->get($currentPlayer->getRColor())) !== null) {
							if (($receiver = $playerManager->getByName($name)) !== null) {
								if ($faction->credits >= $credit) {
									$faction->decreaseCredit($credit);
									$playerManager->increaseCredit($receiver, $credit);

									# create the transaction
									$ct = new CreditTransaction();
									$ct->rSender = $currentPlayer->getRColor();
									$ct->type = CreditTransaction::TYP_F_TO_P;
									$ct->rReceiver = $receiver->id;
									$ct->amount = $credit;
									$ct->dTransaction = Utils::now();
									$ct->comment = $text;
									$creditTransactionManager->add($ct);

									$n = new Notification();
									$n->setRPlayer($receiver->id);
									$n->setTitle('Réception de crédits');
									$n->addBeg();
									$n->addTxt('Votre faction vous a envoyé des crédits');
									if ($text !== '') {
										$n->addTxt(' avec le message suivant : ')->addBrk()->addTxt('"' . $text . '"');
									} else {
										$n->addTxt('.');
									}
									$n->addBoxResource('credit', Format::numberFormat($credit), ($credit == 1 ? 'crédit reçu' : 'crédits reçus'), $this->getParameter('media'));
									$n->addEnd();
									$notificationManager->add($n);
									$this->addFlash('success', 'Crédits envoyés');
									$entityManager->flush();

									return $this->redirect($request->headers->get('referer'));
								} else {
									throw new ErrorException('envoi de crédits impossible - vous ne pouvez pas envoyer plus que ce que vous possédez');
								}
							} else {
								throw new ErrorException('envoi de crédits impossible - erreur dans les joueurs');
							}
						} else {
							throw new ErrorException('envoi de crédits impossible - erreur dans la faction');
						}
					} else {
						throw new ErrorException('envoi de crédits impossible - il faut envoyer un nombre entier positif');
					}
				} else {
					throw new FormException('le texte ne doit pas dépasser les 500 caractères');
				}
			} else {
				throw new ErrorException('Seul le responsable financier de votre faction peut faire cette action.');
			}
		} else {
			throw new FormException('pas assez d\'informations pour envoyer des crédits');
		}
	}
}
