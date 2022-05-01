<?php

namespace App\Modules\Zeus\Helper;

use App\Classes\Database\Database;

class PlayerHelper
{
	public function __construct(protected Database $database)
	{
	}

	/**
	 * @return bool
	 */
	public function listOrbitalBases(int $playerId): array
	{
		$qr = $this->database->prepare('SELECT 
				ob.rPlace, ob.name, sy.rSector
			FROM orbitalBase AS ob
			LEFT JOIN place AS pl
				ON pl.id = ob.rPlace
			LEFT JOIN system AS sy
				ON sy.id = pl.rSystem
			WHERE ob.rPlayer = ?');
		$qr->execute([$playerId]);
		/** @TODO Fetch correctly that monstruosity **/
		$aw = $qr->fetchAll();
		if (empty($aw)) {
			return false;
		} else {
			foreach ($aw as $k => $v) {
				$return[] = ['id' => $v['rPlace'], 'name' => $v['name'], 'sector' => $v['rSector']];
			}

			return $return;
		}
	}
}
