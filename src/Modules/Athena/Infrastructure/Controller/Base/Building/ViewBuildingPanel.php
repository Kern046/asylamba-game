<?php

namespace App\Modules\Athena\Infrastructure\Controller\Base\Building;

use App\Classes\Library\Chronos;
use App\Classes\Library\Format;
use App\Classes\Library\Game;
use App\Modules\Athena\Helper\OrbitalBaseHelper;
use App\Modules\Athena\Model\OrbitalBase;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Gaia\Resource\PlaceResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ViewBuildingPanel extends AbstractController
{
	public function __invoke(
		OrbitalBase $currentBase,
		OrbitalBaseHelper $orbitalBaseHelper,
		int $identifier,
	): Response {
		if (!$orbitalBaseHelper->isABuilding($identifier)) {
			throw $this->createNotFoundException('This building does not exist');
		}

		$currentLevel = $currentBase->getBuildingLevel($identifier);
		$max = $orbitalBaseHelper->getBuildingInfo($identifier, 'maxLevel', OrbitalBase::TYP_CAPITAL);

		$noteQuantity = 0;
		$footnoteArray = array();
		for ($i = 0; $i < $max; $i++) {
			$level = $i + 1;
			# generate the exponents for the footnotes
			$alreadyANote = FALSE;
			$note = '';
			for ($j = 0; $j < 4; $j++) {
				if ($i == $orbitalBaseHelper->getInfo($identifier, 'maxLevel', $j) - 1) {
					if (!$alreadyANote) {
						$alreadyANote = TRUE;
						$noteQuantity++;
						$note .= '<sup>' . $noteQuantity . '</sup>';
					}
					$footnoteArray[$j] = $noteQuantity;
				}
			}
			$data[$i] = [
				'note' => $level . $note,
				'resourcePrice' => Format::numberFormat($orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'resourcePrice')),
				'time' => Chronos::secondToFormat($orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'time'), 'lite'),
				'stats' => match ($identifier) {
					OrbitalBaseResource::GENERATOR => [
						['stat' => Format::numberFormat($orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'nbQueues'))],
					],
					OrbitalBaseResource::REFINERY => [
						[
							'stat' => Format::numberFormat(Game::resourceProduction($orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'refiningCoefficient'), 50)),
							'image' => 'resource',
							'alt' => 'resources',
						],
					],
					OrbitalBaseResource::STORAGE => [
						[
							'stat' => Format::numberFormat($orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'storageSpace')),
							'image' => 'resource',
							'alt' => 'resources',
						],
					],
					OrbitalBaseResource::DOCK1 => [
						[
							'stat' => Format::numberFormat($orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'nbQueues')),
						],
						[
							'stat' => Format::numberFormat($orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'storageSpace')),
							'image' => 'pev',
							'alt' => 'pev',
						],
					],
					OrbitalBaseResource::DOCK2 => [
						[
							'stat' => Format::numberFormat($orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'nbQueues')),
						],
						[
							'stat' => Format::numberFormat($orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'storageSpace')),
							'image' => 'pev',
							'alt' => 'pev',
						],
					],
					OrbitalBaseResource::TECHNOSPHERE => [
						[
							'stat' => Format::numberFormat($orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'nbQueues')),
						],
					],
					OrbitalBaseResource::COMMERCIAL_PLATEFORME => [
						[
							'stat' => Format::numberFormat($orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'nbCommercialShip')),
						],
					],
					OrbitalBaseResource::RECYCLING => [
						[
							'stat' => Format::numberFormat($orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'nbRecyclers')),
						],
					],
					OrbitalBaseResource::SPATIOPORT => [
						[
							'stat' => Format::numberFormat($orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'nbRoutesMax')),
						],
					],
					default => throw new \LogicException(),
				},
				'points' => $orbitalBaseHelper->getBuildingInfo($identifier, 'level', $level, 'points'),
			];
		}
		
		return $this->render('blocks/athena/building_panel.html.twig', [
			'footnote_array' => $footnoteArray,
			'quantity_array' => array_count_values($footnoteArray),
			'data' => $data,
			'current_level' => $currentLevel,
			'max_level' => $max,
			'building_number' => $identifier,
		]);
	}
}
