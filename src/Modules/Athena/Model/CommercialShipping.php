<?php

namespace App\Modules\Athena\Model;

use App\Modules\Zeus\Model\Player;
use App\Shared\Domain\Model\TravellerInterface;
use Symfony\Component\Uid\Uuid;

class CommercialShipping implements TravellerInterface
{
	// statement
	public const ST_WAITING = 0;		// pret au départ, statique
	public const ST_GOING = 1;			// aller
	public const ST_MOVING_BACK = 2;	// retour

	public const WEDGE = 1000;	// soute

	// attributes
	public function __construct(
		public Uuid $id,
		public Player $player,
		public OrbitalBase $originBase,
		public OrbitalBase|null $destinationBase = null,
		public Transaction|null $transaction = null,
		// @TODO rather have a shipment type and then a quantity field
		public int|null $resourceTransported = null, // soit l'un
		public int $shipQuantity = 0, // soit l'autre
		public int $statement = 0,
		public \DateTimeImmutable|null $departureDate = null,
		public \DateTimeImmutable|null $arrivalDate = null,
	) {

	}

	public function isMoving(): bool
	{
		return self::ST_GOING === $this->statement;
	}

	public function isMovingBack(): bool
	{
		return self::ST_MOVING_BACK === $this->statement;
	}

	public function isWaiting(): bool
	{
		return self::ST_WAITING === $this->statement;
	}

	public function getDepartureDate(): \DateTimeImmutable|null
	{
		return $this->departureDate;
	}

	public function getArrivalDate(): \DateTimeImmutable|null
	{
		return $this->arrivalDate;
	}
}
