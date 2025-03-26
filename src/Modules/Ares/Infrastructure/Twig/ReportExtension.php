<?php

namespace App\Modules\Ares\Infrastructure\Twig;

use App\Modules\Ares\Model\Commander;
use App\Modules\Ares\Model\Report;
use App\Modules\Zeus\Application\Registry\CurrentPlayerRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ReportExtension extends AbstractExtension
{
	public function __construct(
		private readonly CurrentPlayerRegistry $currentPlayerRegistry,
	) {

	}

	#[\Override]
    public function getFunctions(): array
	{
		return [
			new TwigFunction('get_report_data', function (Report $report) {
				if ($report->attacker->faction->identifier === $this->currentPlayerRegistry->get()->faction->identifier) {
					if ($report->winner?->id === $report->attacker->id) {
						if ($report->type === Commander::LOOT) {
							$title = 'Pillage';
							$img = 'loot.png';
						} else {
							$title = null === $report->defender
								? 'Colonisation réussie'
								: 'Conquête';
							$img = 'colo.png';
						}
					} else {
						if ($report->type === Commander::LOOT) {
							$title = 'Pillage raté';
							$img = 'loot.png';
						} else {
							$title = null === $report->defender
								? 'Colonisation ratée'
								: 'Conquête ratée';
							$img = 'colo.png';
						}
					}
				} else {
					if ($report->winner?->id === $report->defender?->id) {
						$title = $report->type === Commander::LOOT
							? 'Pillage repoussé'
							: 'Conquête repoussée';
						$img = 'shield.png';
					} else {
						$title = $report->type === Commander::LOOT
							? 'Défense ratée lors d\'un pillage'
							: 'Défense ratée lors d\'une conquête';
						$img = 'shield.png';
					}
				}

				return ['title' => $title, 'img' => $img];
			}),
		];
	}
}
