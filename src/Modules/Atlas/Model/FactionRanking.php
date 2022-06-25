<?php

namespace App\Modules\Atlas\Model;

use App\Classes\Container\StackList;
use App\Classes\Library\Format;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use Symfony\Component\Uid\Uuid;

class FactionRanking
{
	public function __construct(
		public Uuid $id,
		public Color $faction,
		public int $points,
		public int $pointsPosition,
		public int $pointsVariation,
		public int $newPoints,
		public int $general,
		public int $generalPosition,
		public int $generalVariation,
		public int $wealth,
		public int $wealthPosition,
		public int $wealthVariation,
		public int $territorial,
		public int $territorialPosition,
		public int $territorialVariation,
		public \DateTimeImmutable $createdAt,
	) {
	}

	public function commonRender(StackList $playerInfo, string $mediaPath, string $type = 'general'): string
	{
		$r = '';

		switch ($type) {
			case 'points':
				$pos = $this->pointsPosition;
				$var = $this->pointsVariation;
				break;
			case 'general':
				$pos = $this->generalPosition;
				$var = $this->generalVariation;
				break;
			case 'wealth':
				$pos = $this->wealthPosition;
				$var = $this->wealthVariation;
				break;
			case 'territorial':
				$pos = $this->territorialPosition;
				$var = $this->territorialVariation;
				break;
			default: $var = '';
			$pos = '';
			break;
		}

		$r .= '<div class="player faction color'.$this->rFaction.' '.($playerInfo->get('color') == $this->rFaction ? 'active' : null).'">';
		$r .= '<img src="'.$mediaPath.'faction/flag/flag-'.$this->rFaction.'.png" alt="'.$this->rFaction.'" class="picto" />';

		$r .= '<span class="title">'.ColorResource::getInfo($this->rFaction, 'government').'</span>';
		$r .= '<strong class="name">'.ColorResource::getInfo($this->rFaction, 'popularName').'</strong>';
		$r .= '<span class="experience">';
		switch ($type) {
			case 'points':
				$r .= Format::number($this->points, -1).' points';
				if ($this->newPoints > 0) {
					$r .= ' (+'.Format::number($this->newPoints, -1).' points)';
				}
				break;
			case 'general': $r .= Format::number($this->general, -1).' points';
			break;
			case 'wealth': $r .= Format::number($this->wealth, -1).' crédits';
			break;
			case 'territorial': $r .= Format::number($this->territorial, -1).' points';
			break;
			default: break;
		}
		$r .= '</span>';

		$r .= '<span class="position';
		$r .= 0 == intval($var)
					? null
					: (
						$var > 0
						? ' upper'
						: ' lower'
					)
		;
		$r .= '">'.$pos.'</span>';
		$r .= '</div>';

		return $r;
	}
}
