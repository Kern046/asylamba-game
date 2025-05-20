<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig\Components\Molecules;

use App\Modules\Ares\Model\Commander;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'CommanderSquadron',
	template: 'components/Molecules/Fleet/CommanderSquadron.html.twig',
)]
class CommanderSquadron
{
	public Commander $commander;
	public array $lineCoord;
	public int $key;
}
