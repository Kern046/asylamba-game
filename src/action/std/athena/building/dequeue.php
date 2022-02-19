<?php
# dequeue a building action

# int baseId 		id de la base orbitale
# int building 	 	id du bâtiment

use App\Classes\Library\Utils;
use App\Classes\Library\Flashbag;
use App\Classes\Exception\FormException;
use App\Classes\Exception\ErrorException;
use App\Modules\Athena\Model\BuildingQueue;

$session = $this->getContainer()->get(\App\Classes\Library\Session\SessionWrapper::class);
$request = $this->getContainer()->get('app.request');
$orbitalBaseManager = $this->getContainer()->get(\App\Modules\Athena\Manager\OrbitalBaseManager::class);
$orbitalBaseHelper = $this->getContainer()->get(\App\Modules\Athena\Helper\OrbitalBaseHelper::class);
$buildingQueueManager = $this->getContainer()->get(\App\Modules\Athena\Manager\BuildingQueueManager::class);
$buildingResourceRefund = $this->getContainer()->getParameter('athena.building.building_queue_resource_refund');
$entityManager = $this->getContainer()->get(\App\Classes\Entity\EntityManager::class);

for ($i=0; $i < $session->get('playerBase')->get('ob')->size(); $i++) { 
	$verif[] = $session->get('playerBase')->get('ob')->get($i)->get('id');
}

$baseId = $request->query->get('baseid');
