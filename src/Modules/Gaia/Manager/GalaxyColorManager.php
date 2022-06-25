<?php

namespace App\Modules\Gaia\Manager;

// Process manually the sector factions
class GalaxyColorManager
{

	protected array $system = [];
	protected array $sector = [];
	protected bool $mustApply = true;

	public function __construct(
		private readonly array $availableFactions,
		private readonly int $limitConquestSector
	) {
	}

	public function apply(): void
	{
		$this->mustApply = true;
	}

	public function mustApply(): bool
	{
		return $this->mustApply;
	}

	public function applyAndSave(): void
	{
		$this->loadSystem();
		$this->loadSector();
		$this->changeColorSystem();
		$this->changeColorSector();
		$this->saveSystem();
		$this->saveSector();
	}

	public function loadSystem(): void
	{
		$requestPart = '';
		foreach ($this->availableFactions as $faction) {
			$requestPart .= '(SELECT COUNT(pa.rColor) FROM place AS pl LEFT JOIN player AS pa ON pl.rPlayer = pa.id WHERE pl.rSystem = se.id AND pa.rColor = '.$faction.') AS color'.$faction.',';
		}
		$requestPart = rtrim($requestPart, ','); // to remove last comma

		$query = 'SELECT
			se.id AS id,
			se.rSector AS sector,
			se.rColor AS color,
			(SELECT COUNT(pl.id) FROM place AS pl WHERE pl.rSystem = se.id) AS nbPlace,
			'.$requestPart.'
		FROM system AS se
		ORDER BY se.id';
		$qr = $this->database->query($query);

		while ($aw = $qr->fetch()) {
			$colors = [];
			foreach ($this->availableFactions as $faction) {
				$colors[$faction] = $aw['color'.$faction];
			}
			$this->system[$aw['id']] = [
				'sector' => $aw['sector'],
				'systemColor' => $aw['color'],
				'nbPlace' => $aw['nbPlace'],
				'color' => $colors,
				'hasChanged' => false,
			];
		}
	}

	public function saveSystem(): void
	{
		foreach ($this->system as $k => $v) {
			if (true == $v['hasChanged']) {
				$qr = $this->database->prepare('UPDATE system SET rColor = ? WHERE id = ?');
				$qr->execute([$v['systemColor'], $k]);
			}
		}
	}

	public function loadSector(): void
	{
		$qr = $this->database->query('SELECT id, rColor, prime FROM sector ORDER BY id');
		while ($aw = $qr->fetch()) {
			$this->sector[$aw['id']] = [
				'color' => $aw['rColor'],
				'prime' => $aw['prime'],
				'hasChanged' => false,
			];
		}
	}

	public function saveSector(): void
	{
		foreach ($this->sector as $k => $v) {
			if (true == $v['hasChanged']) {
				$qr = $this->database->prepare('UPDATE sector SET rColor = ?, prime = ? WHERE id = ?');
				$qr->execute([$v['color'], $v['prime'], $k]);
			}
		}
	}

	public function changeColorSystem(): void
	{
		foreach ($this->system as $k => $v) {
			if ($v['systemColor'] + array_sum($v['color']) == 0) {
				// system blanc qui ne change pas
			} elseif (0 != $v['systemColor'] && 0 == array_sum($v['color'])) {
				// system pas blanc devient blanc

				$this->system[$k]['systemColor'] = 0;
				$this->system[$k]['hasChanged'] = true;
			} else {
				// autre cas

				$currColor = $v['systemColor'];

				$usedArray = $v['color'];

				$frsNumber = max($usedArray);
				$temp = array_keys($usedArray, max($usedArray));
				$frsColor = $temp[0];

				unset($usedArray[$frsColor]);

				$secNumber = max($usedArray);
				$temp = array_keys($usedArray, max($usedArray));
				$secColor = $temp[0];

				if (0 == $secNumber) {
					if ($v['systemColor'] != $frsColor) {
						$this->system[$k]['systemColor'] = $frsColor;
						$this->system[$k]['hasChanged'] = true;
					}
				} else {
					if ($frsNumber > $secNumber and $frsColor != $v['systemColor']) {
						$this->system[$k]['systemColor'] = $frsColor;
						$this->system[$k]['hasChanged'] = true;
					}
				}
			}
		}
	}

	public function changeColorSector(): void
	{
		$sectorUpdatedColor = [];

		foreach ($this->sector as $k => $v) {
			$colorRepartition = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

			foreach ($this->system as $m => $n) {
				if ($n['sector'] == $k) {
					if (0 != $n['systemColor']) {
						++$colorRepartition[$n['systemColor'] - 1];
					}
				}
			}

			$nbrColor = max($colorRepartition);

			if (0 == $v['color']) {
				$nbrColorSector = null;
			} else {
				$nbrColorSector = $colorRepartition[$v['color'] - 1];
			}

			if ($nbrColor >= $this->limitConquestSector) {
				$maxColor = array_keys($colorRepartition, max($colorRepartition));
				$this->sector[$k]['prime'] = 0;

				if (null == $nbrColorSector) {
					$sectorUpdatedColor[] = $this->sector[$k]['color'];
					$this->sector[$k]['color'] = $maxColor[0] + 1;
					$this->sector[$k]['hasChanged'] = true;
				} elseif ($nbrColor > $nbrColorSector and ($maxColor[0] + 1) != $v['color']) {
					$sectorUpdatedColor[] = $this->sector[$k]['color'];
					$this->sector[$k]['color'] = $maxColor[0] + 1;
					$this->sector[$k]['hasChanged'] = true;
				}
			} else {
				// ne modifie pas un secteur prime s'il n'y a pas assez de joueur dedans
				if (0 == $this->sector[$k]['prime']) {
					$sectorUpdatedColor[] = $this->sector[$k]['color'];
					$this->sector[$k]['color'] = 0;
					$this->sector[$k]['hasChanged'] = true;
				}
			}
		}
	}
}
