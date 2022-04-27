<?php

// bases loading

use App\Modules\Ares\Model\Commander;

$container = $this->getContainer();
$request = $this->getContainer()->get('app.request');
$session = $this->getContainer()->get(\App\Classes\Library\Session\SessionWrapper::class);
$orbitalBaseManager = $this->getContainer()->get(\App\Modules\Athena\Manager\OrbitalBaseManager::class);
$commanderManager = $this->getContainer()->get(\App\Modules\Ares\Manager\CommanderManager::class);
$recyclingMissionManager = $this->getContainer()->get(\App\Modules\Athena\Manager\RecyclingMissionManager::class);
$recyclingLogManager = $this->getContainer()->get(\App\Modules\Athena\Manager\RecyclingLogManager::class);
$componentPath = $container->getParameter('component');

// choix de la base
$base = $orbitalBaseManager->get($session->get('playerParams')->get('base'));

// background paralax
echo '<div id="background-paralax" class="bases"></div>';

// inclusion des elements
include 'basesElement/subnav.php';
include 'defaultElement/movers.php';

// contenu spécifique
echo '<div id="content">';
    include $componentPath.'publicity.php';
    // obNav component
    if (!$request->query->has('view') || 'main' === $request->query->get('view')) {
        $ob_obSituation = $base;

        $commanders_obSituation = $commanderManager->getBaseCommanders($base->getId(), [Commander::AFFECTED, Commander::MOVING]);

        $ob_index = 0;
        $ob_fastView = $base;
        $fastView_profil = false;
        include $componentPath.'bases/fastView.php';

        include $componentPath.'bases/ob/situation.php';
        include $componentPath.'bases/ob/base-type.php';

        if ($session->get('playerBase')->get('ob')->size() > 1) {
            include $componentPath.'bases/ob/leavebase.php';
        }
    } elseif ('generator' == $request->query->get('view') && $base->levelGenerator > 0) {
        $ob_generator = $base;
        include $componentPath.'bases/ob/generator.php';
    } elseif ('refinery' == $request->query->get('view') && $base->levelRefinery > 0) {
        $ob_refinery = $base;
        include $componentPath.'bases/ob/refinery.php';
    } elseif ('dock1' == $request->query->get('view') && $base->levelDock1 > 0) {
        $ob_dock1 = $base;
        include $componentPath.'bases/ob/dock1.php';
    } elseif ('dock2' == $request->query->get('view') && $base->levelDock2 > 0) {
        $ob_dock2 = $base;
        include $componentPath.'bases/ob/dock2.php';
    } elseif ('technosphere' == $request->query->get('view') && $base->levelTechnosphere > 0) {
        $ob_tech = $base;
        include $componentPath.'bases/ob/technosphere.php';
    } elseif ('commercialplateforme' == $request->query->get('view') && $base->levelCommercialPlateforme > 0) {
        $ob_compPlat = $base;
        include $componentPath.'bases/ob/comPlat.php';
    } elseif ('storage' == $request->query->get('view') && $base->levelStorage > 0) {
        $ob_storage = $base;
        include $componentPath.'bases/ob/storage.php';
    } elseif ('recycling' == $request->query->get('view') && $base->levelRecycling > 0) {
        $ob_recycling = $base;

        // load recycling missions
        $baseMissions = $recyclingMissionManager->getBaseActiveMissions($ob_recycling->rPlace);
        $missionsLogs = $recyclingLogManager->getBaseActiveMissionsLogs($ob_recycling->rPlace);
        $missionQuantity = count($baseMissions);

        include $componentPath.'bases/ob/recycling.php';
        if (0 === $missionQuantity) {
            include $componentPath.'default.php';
        }
    } elseif ('spatioport' == $request->query->get('view') && $base->levelSpatioport > 0) {
        $ob_spatioport = $base;
        include $componentPath.'bases/ob/spatioport.php';
    } elseif ('school' == $request->query->get('view')) {
        $ob_school = $base;
        include $componentPath.'bases/ob/school.php';
    } else {
        $this->getContainer()->get('app.response')->redirect('bases');
    }
echo '</div>';
