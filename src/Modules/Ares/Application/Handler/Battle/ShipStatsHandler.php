<?php

namespace App\Modules\Ares\Application\Handler\Battle;

use App\Modules\Ares\Domain\Model\ShipStat;
use App\Modules\Ares\Model\Ship;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Zeus\Application\Handler\Bonus\BonusApplierInterface;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Modules\Zeus\Model\PlayerBonusId;

readonly class ShipStatsHandler
{
	public function __construct(
		private BonusApplierInterface $bonusApplier,
	) {
	}

	public function getStats(Ship $ship, ShipStat $stat, PlayerBonus|null $playerBonus = null): mixed
	{
		return $this->getStatsByShipNumber($ship->shipNumber, $stat, $playerBonus);
	}
	
	public function getStatsByShipNumber(int $shipNumber, ShipStat $stat, PlayerBonus|null $playerBonus = null): mixed
	{
		$initialValue = ShipResource::getInfo($shipNumber, $stat->value);

		if (null !== $playerBonus && null !== ($bonusId = $this->getBonusIdForStat($shipNumber, $stat))) {
			if (ShipStat::Attack === $stat) {
				return array_map(
					fn (int $damage) => $damage + $this->bonusApplier->apply($damage, $bonusId, $playerBonus),
					$initialValue,
				);
			}

			return $initialValue + $this->bonusApplier->apply(
				$initialValue,
				$bonusId,
				$playerBonus,
			);
		}
		return $initialValue;
	}

	/**
	 * TODO think bout a way to be warned when a ship does not have an associated bonus
	 */
	private function getBonusIdForStat(int $shipNumber, ShipStat $stat): int|null
	{
		return match ($stat) {
			ShipStat::Attack => match ($shipNumber) {
				Ship::TYPE_PEGASE, Ship::TYPE_SATYRE, Ship::TYPE_CHIMERE => PlayerBonusId::FIGHTER_ATTACK,
				Ship::TYPE_SIRENE, Ship::TYPE_DRYADE, Ship::TYPE_MEDUSE => PlayerBonusId::CORVETTE_ATTACK,
				Ship::TYPE_GRIFFON, Ship::TYPE_CYCLOPE => PlayerBonusId::FRIGATE_ATTACK,
				Ship::TYPE_MINOTAURE, Ship::TYPE_HYDRE => PlayerBonusId::DESTROYER_ATTACK,
				default => null,
			},
			ShipStat::Defense => match ($shipNumber) {
				Ship::TYPE_PEGASE, Ship::TYPE_SATYRE, Ship::TYPE_CHIMERE => PlayerBonusId::FIGHTER_DEFENSE,
				Ship::TYPE_SIRENE, Ship::TYPE_DRYADE, Ship::TYPE_MEDUSE => PlayerBonusId::CORVETTE_DEFENSE,
				Ship::TYPE_GRIFFON, Ship::TYPE_CYCLOPE => PlayerBonusId::FRIGATE_DEFENSE,
				Ship::TYPE_MINOTAURE, Ship::TYPE_HYDRE => PlayerBonusId::DESTROYER_DEFENSE,
				default => null,
			},
			ShipStat::Speed => match ($shipNumber) {
				Ship::TYPE_PEGASE, Ship::TYPE_SATYRE, Ship::TYPE_CHIMERE => PlayerBonusId::FIGHTER_SPEED,
				Ship::TYPE_SIRENE, Ship::TYPE_DRYADE, Ship::TYPE_MEDUSE => PlayerBonusId::CORVETTE_SPEED,
				Ship::TYPE_GRIFFON, Ship::TYPE_CYCLOPE => PlayerBonusId::FRIGATE_SPEED,
				Ship::TYPE_MINOTAURE, Ship::TYPE_HYDRE => PlayerBonusId::DESTROYER_SPEED,
				default => null,
			},
			default => null,
		};
	}
}
