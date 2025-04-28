<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig\Components\Organisms;

use App\Modules\Ares\Model\Commander;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'CommanderSquadrons',
	template: 'components/Organisms/Fleet/CommanderSquadrons.html.twig',
)]
final class CommanderSquadrons
{
	public Commander $commander;
}
