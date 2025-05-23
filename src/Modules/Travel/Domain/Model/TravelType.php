<?php

declare(strict_types=1);

namespace App\Modules\Travel\Domain\Model;

enum TravelType: string
{
	case Fleet = 'fleet';
	case CommercialShipping = 'commercial_shipping';
	case RecyclingShips = 'recycling_ships';
}
