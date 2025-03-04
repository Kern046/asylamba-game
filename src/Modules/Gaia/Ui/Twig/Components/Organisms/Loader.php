<?php

declare(strict_types=1);

namespace App\Modules\Gaia\Ui\Twig\Components\Organisms;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'MapLoader',
	template:  'components/Organisms/Map/Loader.html.twig',
)]
class Loader
{

}
