<?php

declare(strict_types = 1);

namespace App\Modules\Zeus\Infrastructure\Twig\Components\Atoms;

use App\Modules\Zeus\Model\Player;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'Avatar',
	template: 'components/Atoms/Player/Avatar.html.twig',
)]
class Avatar
{
	public Player $player;
}
