<?php

/**
 * TransactionManager.
 *
 * @author Jacky Casas
 * @copyright Expansion - le jeu
 *
 * @version 19.11.13
 **/

namespace App\Modules\Athena\Manager;

use App\Classes\Entity\EntityManager;
use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Classes\Library\Session\SessionWrapper;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\Transaction;

class TransactionManager
{
	public function __construct(
		protected EntityManager $entityManager,
		protected CommercialTaxManager $commercialTaxManager,
		protected SessionWrapper $sessionWrapper,
		protected string $rootPath,
		protected string $mediaPath,
	) {
	}

	/**
	 * @param int $id
	 *
	 * @return Transaction
	 */
	public function get($id)
	{
		return $this->entityManager->getRepository(Transaction::class)->get($id);
	}

	/**
	 * @param int $type
	 *
	 * @return Transaction
	 */
	public function getLastCompletedTransaction($type)
	{
		return $this->entityManager->getRepository(Transaction::class)->getLastCompletedTransaction($type);
	}

	/**
	 * @param int $type
	 *
	 * @return array
	 */
	public function getProposedTransactions($type)
	{
		return $this->entityManager->getRepository(Transaction::class)->getProposedTransactions($type);
	}

	/**
	 * @param int $playerId
	 * @param int $type
	 *
	 * @return array
	 */
	public function getPlayerPropositions($playerId, $type)
	{
		return $this->entityManager->getRepository(Transaction::class)->getPlayerPropositions($playerId, $type);
	}

	/**
	 * @param int $placeId
	 *
	 * @return array
	 */
	public function getBasePropositions($placeId)
	{
		return $this->entityManager->getRepository(Transaction::class)->getBasePropositions($placeId);
	}

	public function getExchangeRate($transactionType)
	{
		return $this->entityManager->getRepository(Transaction::class)->getExchangeRate($transactionType);
	}

	public function add(Transaction $transaction)
	{
		$this->entityManager->persist($transaction);
		$this->entityManager->flush($transaction);
	}

	/**
	 * @return array{
	 *  export_price: int,
	 * 	export_tax: int,
	 *  export_faction: int,
	 *  import_price: int,
	 * 	import_tax: int,
	 *  import_faction: int,
	 *  total_price: int,
	 *  time: float,
	 *  rate: float,
	 * }
	 */
	public function getTransactionData(Transaction $transaction, OrbitalBase $ob, float $currentRate = null): array
	{
		//	$rv = '1:' . Format::numberFormat(Game::calculateRate($transaction->type, $transaction->quantity, $transaction->identifier, $transaction->price), 3);
		if (null !== $currentRate) {
			$rate = round(Game::calculateRate($transaction->type, $transaction->quantity, $transaction->identifier, $transaction->price) / $currentRate * 100);
		}
		$time = Game::getTimeTravelCommercial($transaction->rSystem, $transaction->positionInSystem, $transaction->xSystem, $transaction->ySystem, $ob->getSystem(), $ob->getPosition(), $ob->getXSystem(), $ob->getYSystem());

		$S_CTM_T = $this->commercialTaxManager->getCurrentSession();
		$this->commercialTaxManager->newSession();

		$exportTax = 0;
		$importTax = 0;
		$exportFaction = 0;
		$importFaction = 0;

		for ($i = 0; $i < $this->commercialTaxManager->size(); ++$i) {
			$comTax = $this->commercialTaxManager->get($i);

			if ($comTax->faction == $transaction->sectorColor and $comTax->relatedFaction == $ob->sectorColor) {
				$exportTax = $comTax->exportTax;
				$exportFaction = $comTax->faction;
			}
			if ($comTax->faction == $ob->sectorColor and $comTax->relatedFaction == $transaction->sectorColor) {
				$importTax = $comTax->importTax;
				$importFaction = $comTax->faction;
			}
		}
		$this->commercialTaxManager->changeSession($S_CTM_T);

		$exportPrice = round($transaction->price * $exportTax / 100);
		$importPrice = round($transaction->price * $importTax / 100);

		return [
			'export_price' => $exportPrice,
			'export_tax' => $exportTax,
			'export_faction' => $exportFaction,
			'import_price' => $importPrice,
			'import_tax' => $importTax,
			'import_faction' => $importFaction,
			'total_price' => $transaction->price + $exportPrice + $importPrice,
			'time' => $time,
			'rate' => $rate ?? null,
		];
	}
}
