<?php

namespace App\Modules\Demeter\Application\Law;

use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Gaia\Model\Sector;
use App\Modules\Zeus\Model\Player;

class SectorTax
{
	/**
	 * @param array{sector: Sector, taxes: int} $data
	 */
	public function create(Player $currentPlayer, array $data): Law
	{
		$sector = $data['sector'] ?? throw new \InvalidArgumentException('Missing sector');
		$taxes = $data['taxes'] ?? throw new \InvalidArgumentException('Missing taxes');

		if ($taxes < 2 || $taxes > 15) {
			throw new \LogicException('La taxe doit être entre 2 et 15 %.');
		}
		if ($sector->faction->id === $currentPlayer->faction->id) {
			$law->options = [
				'taxes' => $taxes,
				'sector' => $sector->id,
				'display' => [
					'Secteur' => $sector->name,
					'Taxe actuelle' => $sector->tax.' %',
					'Taxe proposée' => $taxes.' %'
				],
			];
			$lawManager->add($law);
			$faction->credits -= LawResources::getInfo($type, 'price');
			$colorManager->sendSenateNotif($faction);
		} else {
			throw new ErrorException('Ce secteur n\'est pas sous votre contrôle.');
		}
	}
}
