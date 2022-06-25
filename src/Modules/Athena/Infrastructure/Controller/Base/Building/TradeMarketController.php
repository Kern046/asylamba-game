<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Modules\Ares\Domain\Repository\CommanderRepositoryInterface;
use App\Modules\Ares\Manager\CommanderManager;
use App\Modules\Ares\Model\Commander;
use App\Modules\Athena\Domain\Repository\CommercialShippingRepositoryInterface;
use App\Modules\Athena\Domain\Repository\TransactionRepositoryInterface;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Manager\TransactionManager;
use App\Modules\Athena\Model\CommercialShipping;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\Transaction;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TradeMarketController extends AbstractController
{
	public function __construct(
		private readonly CommercialShippingRepositoryInterface $commercialShippingRepository,
	) {

	}

	public function __invoke(
		CommanderRepositoryInterface   $commanderRepository,
		OrbitalBase                    $currentBase,
		OrbitalBaseHelper              $orbitalBaseHelper,
		TransactionRepositoryInterface $transactionRepository,
		string                         $mode,
	): Response {
		return $this->render('pages/athena/trade_market.html.twig', [
			'mode' => $mode,
			'max_ships' => $orbitalBaseHelper->getInfo(
				OrbitalBaseResource::COMMERCIAL_PLATEFORME,
				'level',
				$currentBase->levelCommercialPlateforme,
				'nbCommercialShip',
			),
			'resources_current_rate' => $transactionRepository->getLastCompletedTransaction(Transaction::TYP_RESOURCE)->currentRate,
			'resource_transactions' => $transactionRepository->getProposedTransactions(Transaction::TYP_RESOURCE),
			'commander_current_rate' => $transactionRepository->getLastCompletedTransaction(Transaction::TYP_COMMANDER)->currentRate,
			'commander_transactions' => $transactionRepository->getProposedTransactions(Transaction::TYP_COMMANDER),
			'ship_current_rate' => $transactionRepository->getLastCompletedTransaction(Transaction::TYP_SHIP)->currentRate,
			'ship_transactions' => $transactionRepository->getProposedTransactions(Transaction::TYP_SHIP),
			'commercial_shippings' => $this->getCommercialShippingsData($currentBase),
			'base_commanders' => $commanderRepository->getBaseCommanders(
				$currentBase,
				[Commander::INSCHOOL, Commander::RESERVE],
				['experience' => 'DESC'],
			),
		]);
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function getCommercialShippingsData(OrbitalBase $currentBase): array
	{
		$commercialShippingsData = [
			'used_ships' => 0,
			'incoming' => [
				CommercialShipping::ST_WAITING => [],
				CommercialShipping::ST_GOING => [],
				CommercialShipping::ST_MOVING_BACK => [],
			],
			'outgoing' => [
				CommercialShipping::ST_WAITING => [],
				CommercialShipping::ST_GOING => [],
				CommercialShipping::ST_MOVING_BACK => [],
			],
		];
		$commercialShippings = $this->commercialShippingRepository->getByBase($currentBase);

		foreach ($commercialShippings as $commercialShipping) {
			if ($commercialShipping->originBase->id === $currentBase->id) {
				$commercialShippingsData['used_ships'] += $commercialShipping->shipQuantity;
				$commercialShippingsData['outgoing'][$commercialShipping->statement][] = $commercialShipping;
			}
			if ($commercialShipping->destinationBase?->id === $currentBase->id) {
				$commercialShippingsData['incoming'][$commercialShipping->statement][] = $commercialShipping;
			}
		}

		return $commercialShippingsData;
	}
}
