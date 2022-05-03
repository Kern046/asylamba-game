<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Offer;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Library\Utils;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\CommercialShippingManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Manager\TransactionManager;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Zeus\Application\Registry\CurrentPlayerBonusRegistry;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use App\Modules\Zeus\Model\PlayerBonusId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Cancel extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		CurrentPlayerBonusRegistry $currentPlayerBonusRegistry,
		TransactionManager $transactionManager,
		CommercialShippingManager $commercialShippingManager,
		OrbitalBaseManager $orbitalBaseManager,
		CommanderManager $commanderManager,
		OrbitalBaseHelper $orbitalBaseHelper,
		PlayerManager $playerManager,
		EntityManager $entityManager,
		int $id,
	): Response {
		$transaction = $transactionManager->get($id);

		$commercialShipping = $commercialShippingManager->getByTransactionId($id);

		if (null !== $transaction and null !== $commercialShipping and Transaction::ST_PROPOSED == $transaction->statement and $transaction->rPlayer == $currentPlayer->getId()) {
			$base = $orbitalBaseManager->get($transaction->rPlace);

			if ($currentPlayer->getCredit() >= $transaction->getPriceToCancelOffer()) {
				$valid = true;

				switch ($transaction->type) {
					case Transaction::TYP_RESOURCE:
						$maxStorage = $orbitalBaseHelper->getBuildingInfo(OrbitalBaseResource::STORAGE, 'level', $base->getLevelStorage(), 'storageSpace');
						$storageBonus = $currentPlayerBonusRegistry->getPlayerBonus()->bonuses->get(PlayerBonusId::REFINERY_STORAGE);
						if ($storageBonus > 0) {
							$maxStorage += ($maxStorage * $storageBonus / 100);
						}
						$storageSpace = $maxStorage - $base->getResourcesStorage();

						if ($storageSpace >= $transaction->quantity) {
							$orbitalBaseManager->increaseResources($base, $transaction->quantity, true);
						} else {
							$valid = false;
							throw new ErrorException('Vous n\'avez pas assez de place dans votre Stockage pour stocker les ressources. Videz un peu le hangar et revenez plus tard pour annuler cette offre.');
						}
						break;
					case Transaction::TYP_SHIP:
						$orbitalBaseManager->addShipToDock($base, $transaction->identifier, $transaction->quantity);
						break;
					case Transaction::TYP_COMMANDER:
						$commander = $commanderManager->get($transaction->identifier);
						$commander->setStatement(Commander::RESERVE);
						break;
					default:
						$valid = false;
				}

				if ($valid) {
					// débit des crédits au joueur
					$playerManager->decreaseCredit($currentPlayer, $transaction->getPriceToCancelOffer());

					// annulation de l'envoi commercial (libération des vaisseaux de commerce)
					$entityManager->remove($commercialShipping);

					// update transaction statement
					$transaction->statement = Transaction::ST_CANCELED;
					$transaction->dValidation = Utils::now();

					$this->addFlash('market_success', match ($transaction->type) {
						Transaction::TYP_RESOURCE => 'Annulation de la proposition commerciale. Les vaisseaux commerciaux sont à nouveau disponibles et vous récupérez vos ressources.',
						Transaction::TYP_SHIP => 'Annulation de la proposition commerciale. Les vaisseaux commerciaux sont à nouveau disponibles et vous récupérez vos vaisseaux de combat.',
						Transaction::TYP_COMMANDER => 'Annulation de la proposition commerciale. Les vaisseaux commerciaux sont à nouveau disponibles et votre commandant est placé à l\'école de commandement.',
					});

					$entityManager->flush();
				}

				return $this->redirect($request->headers->get('referer'));
			} else {
				throw new ErrorException('vous n\'avez pas assez de crédits pour annuler la proposition');
			}
		} else {
			throw new ErrorException('impossible d\'annuler une proposition sur le marché');
		}
	}
}
