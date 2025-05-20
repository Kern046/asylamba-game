<?php

declare(strict_types=1);

namespace App\Modules\Athena\Infrastructure\Twig\Components\Molecules;

use App\Classes\Library\Format;
use App\Modules\Athena\Infrastructure\Controller\Ship\CancelQueue;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Shared\Infrastructure\Twig\Components\Molecules\Queue;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use App\Modules\Athena\Model\ShipQueue as ShipQueueModel;

#[AsTwigComponent(
	name: 'ShipQueue',
	template: 'components/Molecules/Queue.html.twig'
)]
class ShipQueue extends Queue
{
	public ShipQueueModel|null $queue = null;

	public function getName(): string
	{
		return sprintf('%d %s%s',
			$this->queue->quantity,
			ShipResource::getInfo($this->queue->shipNumber, 'codeName'),
			Format::plural($this->queue->quantity),
		);
	}

	public function getSubTitle(): string
	{
		return '';
	}

	public function getCancelRoute(): string
	{
		return CancelQueue::ROUTE_NAME;
	}

	public function getCancelParameters(): array
	{
		return [
			'id' => $this->queue->id,
		];
	}

	public function getPicto(): string
	{
		return sprintf(
			'images/legacy/picto/ships/types/%s.svg',
			ShipResource::getInfo($this->getIdentifier(), 'picto'),
		);
	}

	public function getIdentifier(): int
	{
		return $this->queue->shipNumber
			?? throw new \LogicException('You cannot use identifier property on an empty queue');
	}
}
