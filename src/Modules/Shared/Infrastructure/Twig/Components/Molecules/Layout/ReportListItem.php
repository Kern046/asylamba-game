<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Twig\Components\Molecules\Layout;

use App\Modules\Artemis\Model\SpyReport;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
	name: 'ReportListItem',
	template: 'components/Molecules/Layout/ReportListItem.html.twig'
)]
class ReportListItem
{
	public string $href;
	public string $placeName;
	public bool $isActive = false;
	public \DateTimeInterface $createdAt;
}
