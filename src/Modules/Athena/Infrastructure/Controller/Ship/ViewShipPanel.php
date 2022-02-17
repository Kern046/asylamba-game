<?php

namespace App\Modules\Athena\Infrastructure\Controller\Ship;

use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Zeus\Model\PlayerBonus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewShipPanel extends AbstractController
{
	public function __invoke(
		Request $request,
		int $shipNumber,
	): Response {
		$session = $request->getSession();

		switch(ShipResource::getInfo($shipNumber, 'class')) {
			case 0:
				$bonusSPE = $session->get('playerBonus')->get(PlayerBonus::FIGHTER_SPEED);
				$bonusATT = $session->get('playerBonus')->get(PlayerBonus::FIGHTER_ATTACK);
				$bonusDEF = $session->get('playerBonus')->get(PlayerBonus::FIGHTER_DEFENSE); break;
			case 1:
				$bonusSPE = $session->get('playerBonus')->get(PlayerBonus::CORVETTE_SPEED);
				$bonusATT = $session->get('playerBonus')->get(PlayerBonus::CORVETTE_ATTACK);
				$bonusDEF = $session->get('playerBonus')->get(PlayerBonus::CORVETTE_DEFENSE); break;
			case 2:
				$bonusSPE = $session->get('playerBonus')->get(PlayerBonus::FRIGATE_SPEED);
				$bonusATT = $session->get('playerBonus')->get(PlayerBonus::FRIGATE_ATTACK);
				$bonusDEF = $session->get('playerBonus')->get(PlayerBonus::FRIGATE_DEFENSE); break;
			case 3:
				$bonusSPE = $session->get('playerBonus')->get(PlayerBonus::DESTROYER_SPEED);
				$bonusATT = $session->get('playerBonus')->get(PlayerBonus::DESTROYER_ATTACK);
				$bonusDEF = $session->get('playerBonus')->get(PlayerBonus::DESTROYER_DEFENSE); break;
			default:
				$bonusSPE = 0;
				$bonusATT = 0;
				$bonusDEF = 0; break;
		}

		# MAXIMA
		$attacks = ShipResource::getInfo($shipNumber, 'attack');

		return $this->render('blocks/athena/ship_panel.html.twig', [
			'ship_number' => $shipNumber,
			'bonus_att' => $bonusATT,
			'bonus_def' => $bonusDEF,
			'bonus_spe' => $bonusSPE,
			'attacks' => $attacks,
			'damage' => array_unique($attacks),
			'nb_shots'=> array_count_values($attacks),
			'life' => ($shipNumber > 5) ? 1600 : 135,
			'defense' => ($shipNumber > 5) ? 200 : 25,
			'speed_a' => ($shipNumber > 5) ? 25 : 100,
			'speed_b' => ($shipNumber > 5) ? 250 : 150,
			'attack' => ($shipNumber > 5) ? 700 : 90,
		]);
	}
}
