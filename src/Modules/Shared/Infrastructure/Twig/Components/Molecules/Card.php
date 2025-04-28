<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Molecules;

abstract class Card
{
	public function isDisabled(): bool
	{
		return false;
	}
}
