<?php

namespace App\Modules\Ares\Model;

class Ship
{
	public const TYPE_PEGASE = 0;
	public const TYPE_SATYRE = 1;
	public const TYPE_CHIMERE = 2;
	public const TYPE_SIRENE = 3;
	public const TYPE_DRYADE = 4;
	public const TYPE_MEDUSE = 5;
	public const TYPE_GRIFFON = 6;
	public const TYPE_CYCLOPE = 7;
	public const TYPE_MINOTAURE = 8;
	public const TYPE_HYDRE = 9;
	public const TYPE_CERBERE = 10;
	public const TYPE_PHENIX = 11;

	public function __construct(
		public int $id,
		public int $shipNumber,
		public int $life,
		public Squadron $squadron,
	) {
	}
}
