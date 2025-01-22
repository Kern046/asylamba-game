<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Atoms;

use App\Modules\Demeter\Model\Color;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'ProgressBar',
	template: 'components/Atoms/ProgressBar.html.twig'
)]
class ProgressBar
{
	public Color $faction;
	public int $start = 0;
	public int $current;
	public int $end = 100;

	public function getWidth(): float
	{
		return max((($this->current - $this->start) / ($this->end - $this->start)) * 100, 0);
	}
}
