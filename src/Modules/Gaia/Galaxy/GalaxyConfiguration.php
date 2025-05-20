<?php

namespace App\Modules\Gaia\Galaxy;

use App\Modules\Gaia\Model\Sector;

abstract class GalaxyConfiguration
{
	/**
	 * @var array{
	 *     size: int,
	 *     diag: int,
	 *     mask: int,
	 *     systemProportion: list<int>,
	 *     systemPosition: list<int>|null,
	 *     lineSystemPosition: list<array{
	 *         0: array{0: int, 1: int},
	 *         1: array{0: int, 1: int},
	 *         2: int,
	 *         3: int,
	 *     }>,
	 *     circleSystemPosition: list<array{
	 *          0: array{0: int, 1: int},
	 *          1: int,
	 *          2: int,
	 *          3: int,
	 *      }>,
	 *     population: list<int>,
	 * }
	 */
	public array $galaxy;
	/**
	 * @var list<array{
	 *   id: int,
	 *   beginColor: int,
	 *   vertices: list<int>,
	 *   barycentre: array{0: int, 1: int},
	 *   display: array{0: int, 1: int},
	 *   name: string,
	 *   danger: int,
	 *   points: int,
	 * }>
	 */
	public array $sectors;
	/**
	 * @var list<array{
	 *     id: int,
	 *     name: string,
	 *     placesPropotion: array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int},
	 *     nbrPlaces: array{0: int, 1: int},
	 * }>
	 */
	public array $systems;
	/**
	 * @var list<array{
	 *     id: int,
	 *     name: string,
	 *     resources: int,
	 *     credits: int,
	 *     history: int,
	 * }>
	 */
	public array $places;

	public int $scale = 20;

	public const DNG_CASUAL = 1;
	public const DNG_EASY = 2;
	public const DNG_MEDIUM = 3;
	public const DNG_HARD = 4;
	public const DNG_VERY_HARD = 5;

	public function getSectorCoord(int $i, int $scale = 1, int $xTranslate = 0): string
	{
		$sector = $this->sectors[$i - 1]['vertices'];

		foreach ($sector as $k => $v) {
			$sector[$k] = (($v * $scale) + $xTranslate);
		}

		return implode(', ', $sector);
	}

	/**
	 * @return array{
	 *     x: float,
	 *     y: float,
	 * }
	 */
	public function getSectorCentroid(Sector $sector, int $scale): array
	{
		// Convert string to array of integers
		$vertices = array_map('intval', explode(',', $this->getSectorCoord($sector->identifier, $scale)));

		$num_points = count($vertices) / 2;
		$cx = 0;
		$cy = 0;
		$area = 0;

		for ($i = 0; $i < $num_points; ++$i) {
			$x1 = $vertices[2 * $i];
			$y1 = $vertices[2 * $i + 1];
			$x2 = $vertices[2 * (($i + 1) % $num_points)];
			$y2 = $vertices[2 * (($i + 1) % $num_points) + 1];

			$factor = ($x1 * $y2 - $x2 * $y1);
			$cx += ($x1 + $x2) * $factor;
			$cy += ($y1 + $y2) * $factor;
			$area += $factor;
		}

		$area *= 0.5;
		$cx /= (6 * $area);
		$cy /= (6 * $area);

		return ['x' => $cx, 'y' => $cy];
	}
}
