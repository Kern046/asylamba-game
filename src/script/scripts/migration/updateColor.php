<?php

use App\Modules\Demeter\Model\Color;

echo '<h2>Ajout de isInGame dans color</h2>';

$this->getContainer()->get(\App\Classes\Database\DatabaseAdmin::class)->query('ALTER TABLE `color` ADD `isInGame` TINYINT NULL DEFAULT 0 AFTER `isClosed`;');

$factions = $this->getContainer()->get(\App\Modules\Demeter\Manager\ColorManager::class)->getAll();

foreach ($factions as $faction) {
	$faction->isInGame = 1;
}

$this->getContainer()->get(\App\Classes\Entity\EntityManager::class)->flush(Color::class);
