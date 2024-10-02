<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Atoms;

use App\Modules\Demeter\Model\Color;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'FactionMotto',
	template: 'components/Faction/Atoms/Motto.html.twig',
)]
final class Motto
{
	public Color $faction;
}
