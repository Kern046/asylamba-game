<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Molecules\Trade;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'CommercialShippingAvailableShipsCount',
	template: 'components/Molecules/Base/Trade/CommercialShippingAvailableShipsCount.html.twig',
)]
class CommercialShippingAvailableShipsCount
{
	public int $maxShips;
	public int $usedShips;
}
