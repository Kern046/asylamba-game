<?php

declare(strict_types=1);

namespace App\Modules\Atlas\Model;

use App\Classes\Container\StackList;
use App\Classes\Library\Format;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use Symfony\Component\Uid\Uuid;

class FactionRanking
{
	public function __construct(
		public Uuid $id,
		public Ranking $ranking,
		public Color $faction,
		public int $points,
		public int $pointsPosition,
		public int $pointsVariation,
		public int $newPoints,
		public int $general,
		public int $generalPosition,
		public int $generalVariation,
		public int $wealth,
		public int $wealthPosition,
		public int $wealthVariation,
		public int $territorial,
		public int $territorialPosition,
		public int $territorialVariation,
		public \DateTimeImmutable $createdAt,
	) {
	}
}
