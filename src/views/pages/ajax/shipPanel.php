<?php

use App\Classes\Worker\CTR;
use App\Classes\Library\Format;
use App\Classes\Library\Chronos;
use App\Modules\Athena\Resource\ShipResource;
use App\Modules\Zeus\Model\PlayerBonus;

$container = $this->getContainer();
$appRoot = $container->getParameter('app_root');
$mediaPath = $container->getParameter('media');
$request = $this->getContainer()->get('app.request');
$session = $this->getContainer()->get(\App\Classes\Library\Session\SessionWrapper::class);

