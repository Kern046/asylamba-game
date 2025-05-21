<?php

declare(strict_types=1);

namespace App\Modules\Portal\Infrastructure\Twig\Components\Atoms;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'HeroLink',
	template: 'components/Atoms/Portal/HeroLink.html.twig',
)]
class HeroLink
{
	public string $href;
	public string $label;
	public string $iconPath;
	public string $iconAlt;
}
