<?php

namespace App\Shared\Infrastructure\Twig;

use App\Modules\Athena\Resource\ShipResource;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class JavascriptExtension extends AbstractExtension
{
	public function __construct(private RequestStack $requestStack)
	{
	}

	public function getFunctions(): array
	{
		return [
			new TwigFunction('get_api_endpoint', fn () => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost()),
			new TwigFunction('get_ship_names', fn () => $this->getShipNames()),
			new TwigFunction('get_ship_pevs', fn () => $this->getShipPevs()),
		];
	}

	protected function getShipNames(): array
	{
		$shipsName = [];
		for ($i = 0; $i < 12; ++$i) {
			$shipsName[] = "'".ShipResource::getInfo($i, 'codeName')."'";
		}

		return $shipsName;
	}

	protected function getShipPevs(): array
	{
		$shipsPev = [];
		for ($i = 0; $i < 12; ++$i) {
			$shipsPev[] = ShipResource::getInfo($i, 'pev');
		}

		return $shipsPev;
	}
}
