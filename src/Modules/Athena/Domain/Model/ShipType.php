<?php

declare(strict_types=1);

namespace App\Modules\Athena\Domain\Model;

enum ShipType: string
{
	case Pegase = 'pegase';
	case Satyre = 'satyre';
	case Chimere = 'chimere';
	case Sirene = 'sirene';
	case Dryade = 'dryade';
	case Meduse = 'meduse';
	case Griffon = 'griffon';
	case Cyclope = 'cyclope';
	case Minotaure = 'minotaure';
	case Hydre = 'hydre';
	case Cerbere = 'cerbere';
	case Phenix = 'phenix';

	public function isFemale(): bool
	{
		return match ($this) {
			self::Chimere, self::Sirene, self::Dryade, self::Meduse, self::Hydre => true,
			default => false,
		};
	}

	public function getIdentifier(): int
	{
		return match ($this) {
			self::Pegase => 0,
			self::Satyre => 1,
			self::Chimere => 2,
			self::Sirene => 3,
			self::Dryade => 4,
			self::Meduse => 5,
			self::Griffon => 6,
			self::Cyclope => 7,
			self::Minotaure => 8,
			self::Hydre => 9,
			self::Cerbere => 10,
			self::Phenix => 11,
		};
	}

	public function getDockType(): DockType
	{
		return match ($this) {
			self::Pegase, self::Satyre, self::Chimere, self::Sirene, self::Dryade, self::Meduse => DockType::Factory,
			self::Griffon, self::Cyclope, self::Minotaure, self::Hydre, self::Cerbere, self::Phenix => DockType::Shipyard,
		};
	}
}
