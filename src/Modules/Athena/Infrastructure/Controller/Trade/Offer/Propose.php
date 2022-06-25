<?php

namespace App\Modules\Athena\Infrastructure\Controller\Trade\Offer;

use App\Classes\Library\Game;
use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\CommercialShippingManager;
use App\Modules\Athena\Manager\OrbitalBaseManager;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Uid\Uuid;

class Propose extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		OrbitalBase $currentBase,
		OrbitalBaseManager $orbitalBaseManager,
		OrbitalBaseHelper $orbitalBaseHelper,
		CommanderManager $commanderManager,
		CommanderRepositoryInterface $commanderRepository,
		TransactionRepositoryInterface $transactionRepository,
		CommercialShippingManager $commercialShippingManager,
		CommercialShippingRepositoryInterface $commercialShippingRepository,
	): Response {
		$type = $request->query->get('type') ?? throw new BadRequestHttpException('Missing type');
		$quantity = $request->request->get('quantity');
		// TODO ATM the identifier contains ship number or commander UUID. Modify the form to keep only ship number
		$identifier = $request->query->get('identifier');
		$price = $request->request->get('price') ?? throw new BadRequestHttpException('Missing price');

		$valid = true;

		// TODO Move to validator component constraint
		switch ($type) {
			case Transaction::TYP_RESOURCE:
				if (false !== $quantity and intval($quantity) > 0) {
					$identifier = 0;
				} else {
					$valid = false;
				}
				break;
			case Transaction::TYP_SHIP:
				if (false !== $identifier and ShipResource::isAShip($identifier)) {
					if (ShipResource::isAShipFromDock1($identifier) or ShipResource::isAShipFromDock2($identifier)) {
						if (false === $quantity) {
							$quantity = 1;
						} else {
							if (intval($quantity) < 1) {
								$valid = false;
							}
						}
					} else {
						$valid = false;
					}
				} else {
					$valid = false;
				}
				break;
			case Transaction::TYP_COMMANDER:
				if (false === $identifier or $identifier < 1) {
					$valid = false;
				}
				break;
			default:
				$valid = false;
		}
		if (!$valid) {
			throw new BadRequestHttpException('impossible de faire une proposition sur le marché');
		}
		$minPrice = Game::getMinPriceRelativeToRate($type, $quantity, $identifier);
		$maxPrice = Game::getMaxPriceRelativeToRate($type, $quantity, $identifier);

		// TODO Move to a validator constraint (same as above ?)
		if ($price < $minPrice) {
			throw new BadRequestHttpException('Le prix que vous avez fixé est trop bas. Une limite inférieure est fixée selon la catégorie de la vente.');
		} elseif ($price > $maxPrice) {
			throw new BadRequestHttpException('Le prix que vous avez fixé est trop haut. Une limite supérieure est fixée selon la catégorie de la vente.');
		}
		// verif : have we enough commercialShips
		$totalShips = $orbitalBaseHelper->getBuildingInfo(OrbitalBaseResource::COMMERCIAL_PLATEFORME, 'level', $currentBase->levelCommercialPlateforme, 'nbCommercialShip');
		$usedShips = 0;

		$commercialShippings = $commercialShippingRepository->getByBase($currentBase);

		foreach ($commercialShippings as $commercialShipping) {
			if ($commercialShipping->originBase->id === $currentBase->id) {
				$usedShips += $commercialShipping->shipQuantity;
			}
		}

		// determine commercialShipQuantity needed
		// TODO Move to service method
		switch ($type) {
			case Transaction::TYP_RESOURCE:
				if ($currentBase->resourcesStorage >= $quantity) {
					$commercialShipQuantity = Game::getCommercialShipQuantityNeeded($type, $quantity);
				} else {
					$valid = false;
				}
				break;
			case Transaction::TYP_SHIP:
				$inStorage = $currentBase->getShipStorage()[$identifier];
				if ($inStorage >= $quantity) {
					$commercialShipQuantity = Game::getCommercialShipQuantityNeeded($type, $quantity, $identifier);
				} else {
					$valid = false;
				}
				break;
			case Transaction::TYP_COMMANDER:
				$commercialShipQuantity = Game::getCommercialShipQuantityNeeded($type, $quantity);
				break;
		}

		$remainingShips = $totalShips - $usedShips;
		if (!$valid) {
			throw match ($type) {
				Transaction::TYP_RESOURCE => new ConflictHttpException('Vous n\'avez pas assez de ressources en stock.'),
				Transaction::TYP_SHIP => new ConflictHttpException('Vous n\'avez pas assez de vaisseaux.'),
				default => new \RuntimeException('Erreur pour une raison étrange, contactez un administrateur.'),
			};
		}
		if ($remainingShips < $commercialShipQuantity) {
			throw new ConflictHttpException('Vous n\'avez pas assez de vaisseaux de transport disponibles.');
		}
		switch ($type) {
			case Transaction::TYP_RESOURCE:
				$orbitalBaseManager->decreaseResources($currentBase, $quantity);
				break;
			case Transaction::TYP_SHIP:
				$inStorage = $currentBase->getShipStorage()[$identifier];
				$currentBase->addShips($identifier, $inStorage - $quantity);
				break;
			case Transaction::TYP_COMMANDER:
				if (($commander = $commanderRepository->get(Uuid::fromString($identifier))) !== null && $commander->player->id === $currentPlayer->id && !$commander->isOnSale()) {
					$identifier = 0;
					$commander->statement = Commander::ONSALE;
					$commanderManager->emptySquadrons($commander);
				} else {
					$valid = false;
				}
				break;
		}

		if (!$valid) {
			throw new ConflictHttpException('Il y a un problème avec votre commandant.');
		}
		// création de la transaction
		$tr = new Transaction(
			id: Uuid::v4(),
			player: $currentPlayer,
			base: $currentBase,
			type: $type,
			quantity: $quantity,
			identifier: $identifier,
			price: $price,
			commercialShipQuantity: $commercialShipQuantity,
			statement: Transaction::ST_PROPOSED,
			publishedAt: new \DateTimeImmutable(),
			currentRate: $transactionRepository->getLastCompletedTransaction($type)->currentRate,
		);

		if ($tr->hasCommander()) {
			$tr->commander = $commander ?? throw new \RuntimeException('Commander is unreachable');
		}

		$transactionRepository->save($tr);

		// création du convoi
		$cs = new CommercialShipping(
			id: Uuid::v4(),
			player: $currentPlayer,
			originBase: $currentBase,
			transaction: $tr,
			shipQuantity: $commercialShipQuantity,
			statement: CommercialShipping::ST_WAITING,
		);
		$commercialShippingManager->add($cs);

		$transactionRepository->save($tr);

		$this->addFlash('market_success', 'Votre proposition a été envoyée sur le marché.');

		return $this->redirect($request->headers->get('referer'));
	}
}
