<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig\Components\Organisms;

use App\Modules\Ares\Model\Commander;
use App\Modules\Artemis\Model\SpyReport;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'FleetSpyReport',
	template: 'components/Organisms/Fleet/FleetSpyReport.html.twig'
)]
final class FleetSpyReport
{
	public SpyReport $spyReport;
	public array|null $commander = null;
	/** @var list<int|string>  */
	public array $army;
}
