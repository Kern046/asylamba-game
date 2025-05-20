<?php

declare(strict_types=1);

namespace App\Modules\Athena\Application\Registry;

use App\Modules\Athena\Domain\Exception\NoCurrentBaseSetException;
use App\Modules\Athena\Model\OrbitalBase;
use Symfony\Component\Uid\Uuid;

class CurrentPlayerBasesRegistry
{
	/**
	 * @var array<string, OrbitalBase>
	 */
	private array $bases = [];

	private Uuid|null $currentBaseId = null;

	/**
	 * @param list<OrbitalBase> $bases
	 */
	public function setBases(array $bases): void
	{
		$this->bases = array_reduce($bases, function (array $acc, OrbitalBase $orbitalBase) {
			$acc[$orbitalBase->id->toBase32()] = $orbitalBase;

			return $acc;
		}, []);
	}

	public function get(Uuid $baseId): OrbitalBase|null
	{
		return $this->bases[$baseId->toBase32()] ?? null;
	}

	public function current(): OrbitalBase
	{
		if (null === $this->currentBaseId) {
			throw new NoCurrentBaseSetException();
		}

		return $this->bases[$this->currentBaseId->toBase32()];
	}

	public function first(): OrbitalBase
	{
		return array_values($this->bases)[0];
	}

	public function next(): OrbitalBase|null
	{
		$currentIndex = array_search($this->currentBaseId->toBase32(), array_keys($this->bases));

		return array_values($this->bases)[$currentIndex + 1] ?? null;
	}

	public function setCurrentBase(Uuid $currentBaseId): void
	{
		$this->currentBaseId = $currentBaseId;
	}

	public function count(): int
	{
		return count($this->bases);
	}

	/** @return array<string, OrbitalBase> */
	public function all(): array
	{
		return $this->bases;
	}
}
