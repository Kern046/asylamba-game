<?php

namespace App\Modules\Athena\Manager;

use App\Classes\Library\Game;
use App\Modules\Athena\Domain\Repository\CommercialTaxRepositoryInterface;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Model\Transaction;

readonly class TransactionManager
{
	public function __construct(
		private CommercialTaxRepositoryInterface $commercialTaxRepository,
	) {
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
		$transactionSystem = $transaction->base->place->system;
		$baseSystem = $ob->place->system;

		$time = Game::getTimeTravelCommercial(
			$transactionSystem,
			$transaction->base->place->position,
			$baseSystem,
			$ob->place->position,
		);

		$transactionFaction = $transactionSystem->sector->faction;
		$baseFaction = $baseSystem->sector->faction;

		$transactionFactionTax = $this->commercialTaxRepository->getFactionsTax($transactionFaction, $baseFaction);
		$exportTax = $transactionFactionTax->exportTax;
		$exportFaction = $transactionFactionTax->faction;

		$baseFactionTax = $this->commercialTaxRepository->getFactionsTax($baseFaction, $transactionFaction);
		$importTax = $baseFactionTax->importTax;
		$importFaction = $baseFactionTax->faction;

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
