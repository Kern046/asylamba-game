<?php

# building a building action

# int baseid 		id de la base orbitale
# int building 	 	id du bâtiment

use App\Classes\Library\Utils;
use App\Classes\Library\Flashbag;
use App\Classes\Library\DataAnalysis;
use App\Modules\Athena\Resource\OrbitalBaseResource;
use App\Modules\Zeus\Resource\TutorialResource;
use App\Modules\Athena\Model\BuildingQueue;
use App\Modules\Zeus\Model\PlayerBonus;
use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;

$container = $this->getContainer();
$session = $this->getContainer()->get(\App\Classes\Library\Session\SessionWrapper::class);
$database = $this->getContainer()->get(\App\Classes\Database\Database::class);
$orbitalBaseManager = $this->getContainer()->get(\App\Modules\Athena\Manager\OrbitalBaseManager::class);
$buildingQueueManager = $this->getContainer()->get(\App\Modules\Athena\Manager\BuildingQueueManager::class);
$orbitalBaseHelper = $this->getContainer()->get(\App\Modules\Athena\Helper\OrbitalBaseHelper::class);
$technologyManager = $this->getContainer()->get(\App\Modules\Promethee\Manager\TechnologyManager::class);
$tutorialHelper = $this->getContainer()->get(\App\Modules\Zeus\Helper\TutorialHelper::class);
$request = $this->getContainer()->get('app.request');

for ($i=0; $i < $session->get('playerBase')->get('ob')->size(); $i++) { 
	$verif[] = $session->get('playerBase')->get('ob')->get($i)->get('id');
}

$baseId = $request->query->get('baseid');
