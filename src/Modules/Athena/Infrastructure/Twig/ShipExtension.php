<?php

namespace App\Modules\Athena\Infrastructure\Twig;

use App\Modules\Athena\Domain\Service\Ship\GetResourceCost;
use App\Modules\Athena\Domain\Service\Ship\GetTimeCost;
use App\Modules\Athena\Resource\ShipResource;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ShipExtension extends AbstractExtension
{
	public function __construct(
		private readonly GetResourceCost $getResourceCost,
		private readonly GetTimeCost     $getTimeCost,
	) {
	}

	public function getFunctions(): array
	{
		return [
			new TwigFunction('get_ship_info', fn (int $shipNumber, string $info) => ShipResource::getInfo($shipNumber, $info)),
			new TwigFunction('get_ship_time_cost', fn (int $shipNumber, int $dockType, int $quantity) => ($this->getTimeCost)($shipNumber, $dockType, $quantity)),
			new TwigFunction('get_ship_resource_cost', fn (int $shipNumber, int $quantity) => ($this->getResourceCost)($shipNumber, $quantity)),
			new TwigFunction('get_ship_price', fn (int $shipNumber, int|float $shipCurrentRate) => $shipCurrentRate * ShipResource::getInfo($shipNumber, 'resourcePrice')),
		];
	}
}
