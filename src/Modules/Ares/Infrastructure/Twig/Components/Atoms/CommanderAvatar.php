<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig\Components\Atoms;

use App\Modules\Ares\Model\Commander;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'CommanderAvatar',
	template: 'components/Atoms/Fleet/CommanderAvatar.html.twig',
)]
final class CommanderAvatar
{
	public Commander $commander;
}
