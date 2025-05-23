<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Organisms\Layout;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'BaseSelector',
	template: 'components/Organisms/Layout/BaseSelector.html.twig'
)]
class BaseSelector
{

}
