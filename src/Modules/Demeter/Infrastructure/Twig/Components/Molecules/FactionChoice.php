<?php

declare(strict_types=1);

namespace App\Modules\Demeter\Infrastructure\Twig\Components\Molecules;

use App\Modules\Demeter\Model\Color;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class FactionChoice
{
	public Color $faction;
	public int $index;
	public bool $isLast = false;
}
