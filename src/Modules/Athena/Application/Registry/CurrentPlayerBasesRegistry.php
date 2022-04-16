<?php

namespace App\Modules\Athena\Application\Registry;

use App\Modules\Athena\Model\OrbitalBase;

class CurrentPlayerBasesRegistry
{
	private array $bases = [];

	private int $currentBaseId = 0;

	/**
	 * @param list<OrbitalBase> $bases
	 */
	public function setBases(array $bases): void
	{
		$this->bases = array_reduce($bases, function (array $acc, OrbitalBase $orbitalBase) {
			$acc[$orbitalBase->getId()] = $orbitalBase;

			return $acc;
		}, []);
	}

	public function get(int $baseId): OrbitalBase|null
	{
		return $this->bases[$baseId] ?? null;
	}

	public function current(): OrbitalBase
	{
		return $this->bases[$this->currentBaseId];
	}

	public function next(): OrbitalBase|null
	{
		$currentIndex = array_search($this->currentBaseId, array_keys($this->bases));

		return array_values($this->bases)[$currentIndex + 1] ?? null;
	}

	public function setCurrentBase(int $currentBaseId): void
	{
		$this->currentBaseId = $currentBaseId;
	}

	public function count(): int
	{
		return count($this->bases);
	}

	/** @return list<OrbitalBase> */
	public function all(): array
	{
		return $this->bases;
	}
}
