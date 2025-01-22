<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Atoms;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'NumberBox',
	template: 'components/Atoms/NumberBox.html.twig',
)]
final class NumberBox
{
	public string $label;
	public float|null $bonus = null;
}
