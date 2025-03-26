<?php

declare(strict_types=1);

namespace App\Modules\Ares\Infrastructure\Twig\Components\Molecules;

use App\Modules\Artemis\Model\SpyReport;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'SpyReportListItem',
	template: 'components/Molecules/Fleet/SpyReportListItem.html.twig'
)]
class SpyReportListItem
{
	public SpyReport $spyReport;
	public bool $isActive = false;
}
