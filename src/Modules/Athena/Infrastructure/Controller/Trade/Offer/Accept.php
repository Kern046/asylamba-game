<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Offer;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Classes\Library\Utils;
use App\Modules\Athena\Manager\CommercialShippingManager;
use App\Modules\Athena\Manager\CommercialTaxManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\TransactionManager;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Gaia\Manager\PlaceManager;
use App\Modules\Hermes\Manager\NotificationManager;
use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Accept extends AbstractController
{
	public function __invoke(
		Request $request,
		OrbitalBase $currentBase,
		Player $currentPlayer,
		ColorManager $colorManager,
		TransactionManager $transactionManager,
		OrbitalBaseManager $orbitalBaseManager,
		CommercialShippingManager $commercialShippingManager,
		CommercialTaxManager $commercialTaxManager,
		NotificationManager $notificationManager,
		PlaceManager $placeManager,
		PlayerManager $playerManager,
		EntityManager $entityManager,
		int $id,
	): Response {
		$transaction = $transactionManager->get($id);

		$commercialShipping = $commercialShippingManager->getByTransactionId($id);

		if ($transaction !== null AND $commercialShipping !== null AND $transaction->statement == Transaction::ST_PROPOSED) {
			$transactionData = $transactionManager->getTransactionData($transaction, $currentBase);

			if ($currentPlayer->getCredit() >= $transactionData['total_price']) {
				# chargement des joueurs
				$buyer = $currentPlayer;
				$seller = $playerManager->get($transaction->rPlayer);

				if ($seller !== null) {
					// The buyer pays the transaction price + the taxes
					$playerManager->decreaseCredit($buyer, $transactionData['total_price']);
					$playerManager->increaseCredit($seller, $transaction->price);

					# transfert des crédits aux alliances
					if ($transaction->sectorColor != 0) {
						$exportFaction = $colorManager->get($transaction->sectorColor);
						$exportFaction->increaseCredit($transactionData['export_tax']);
					}

					if ($currentBase->sectorColor != 0) {
						$importFaction = $colorManager->get($currentBase->sectorColor);
						$importFaction->increaseCredit($transactionData['import_tax']);
					}

					# gain d'expérience
					$experience = $transaction->getExperienceEarned();
					$playerManager->increaseExperience($seller, $experience);

					# load places to compute travel time
					$startPlace = $placeManager->get($commercialShipping->rBase);
					$destinationPlace = $placeManager->get($currentBase->getRPlace());
					$timeToTravel = Game::getTimeToTravelCommercial($startPlace, $destinationPlace);
					$departure = Utils::now();
					$arrival = Utils::addSecondsToDate($departure, $timeToTravel);

					# update commercialShipping
					$commercialShipping->rBaseDestination = $currentBase->getId();
					$commercialShipping->dDeparture = $departure;
					$commercialShipping->dArrival = $arrival;
					$commercialShipping->statement = CommercialShipping::ST_GOING;

					# update transaction statement
					$transaction->statement = Transaction::ST_COMPLETED;
					$transaction->dValidation = Utils::now();

					# update exchange rate
					$transaction->currentRate = Game::calculateCurrentRate($transactionManager->getExchangeRate($transaction->type), $transaction->type, $transaction->quantity, $transaction->identifier, $transaction->price);

					# notif pour le proposeur
					$n = new Notification();
					$n->setRPlayer($transaction->rPlayer);
					$n->setTitle('Transaction validée');
					$n->addBeg()->addLnk('embassy/player-' . $currentPlayer->getId(), $currentPlayer->getName());
					$n->addTxt(' a accepté une de vos propositions dans le marché. Des vaisseaux commerciaux viennent de partir de votre ');
					$n->addLnk('map/place-' . $commercialShipping->rBase, 'base')->addTxt(' et se dirigent vers ');
					$n->addLnk('map/place-' . $currentBase->getRPlace(), $currentBase->getName())->addTxt(' pour acheminer la marchandise. ');
					$n->addSep()->addTxt('Vous gagnez ' . Format::numberFormat($transaction->price) . ' crédits et ' . Format::numberFormat($experience) . ' points d\'expérience.');
					$n->addSep()->addLnk('action/a-switchbase/base-' . $commercialShipping->rBase . '/page-sell', 'En savoir plus ?');
					$n->addEnd();
					$notificationManager->add($n);

//						if (true === $this->getContainer()->getParameter('data_analysis')) {
//							$qr = $database->prepare('INSERT INTO
//						DA_CommercialRelation(`from`, `to`, type, weight, dAction)
//						VALUES(?, ?, ?, ?, ?)'
//							);
//							$qr->execute([$transaction->rPlayer, $session->get('playerId'), $transaction->type, DataAnalysis::creditToStdUnit($transaction->price), Utils::now()]);
//						}
					$entityManager->flush();

					$this->addFlash('market_success', 'Proposition acceptée. Les vaisseaux commerciaux sont en route vers votre base orbitale.');

					return $this->redirect($request->headers->get('referer'));
				} else {
					throw new ErrorException('erreur dans les propositions sur le marché, joueur inexistant');
				}
			} else {
				throw new ErrorException('vous n\'avez pas assez de crédits pour accepter cette proposition');
			}
		} else {
			throw new ErrorException('erreur dans les propositions sur le marché');
		}
	}
}
