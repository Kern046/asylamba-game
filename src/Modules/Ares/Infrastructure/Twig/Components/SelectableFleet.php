<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig\Components;

use App\Modules\Ares\Model\Commander;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class SelectableFleet
{
	public Commander $commander;
}
