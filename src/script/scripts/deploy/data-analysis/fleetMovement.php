<?php

echo '<h2>Ajout de la table DA_FleetMovement</h2>';

$db = $this->getContainer()->get(\App\Classes\Database\Database::class);
$db->query('DROP TABLE IF EXISTS `DA_FleetMovement`');
$db->query('CREATE TABLE IF NOT EXISTS `DA_FleetMovement` (
	`id` INT unsigned NOT NULL AUTO_INCREMENT,
	`from` INT unsigned NOT NULL,
	`to` INT unsigned NOT NULL,

	`type` SMALLINT unsigned NOT NULL,
	`isWinner` SMALLINT unsigned NOT NULL,
	`weight` INT unsigned NOT NULL,
	`dAction` DATETIME NOT NULL,
	
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;');
