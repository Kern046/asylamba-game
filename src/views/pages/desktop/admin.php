<?php

use App\Classes\Library\Flashbag;

$container = $this->getContainer();
$request = $this->getContainer()->get('app.request');
$response = $this->getContainer()->get('app.response');
$session = $this->getContainer()->get(\App\Classes\Library\Session\SessionWrapper::class);
$componentPath = $container->getParameter('component');
// bases loading
if (false == $session->get('playerInfo')->get('admin')) {
	$session->addFlashbag('Accès non-autorisé', Flashbag::TYPE_BUG_ERROR);
	$response->redirect('profil');

	return;
}

// background paralax
echo '<div id="background-paralax" class="profil"></div>';

// inclusion des elements
include 'adminElement/subnav.php';
include 'defaultElement/movers.php';

// contenu spécifique
echo '<div id="content">';
	// admin component
	if (!$request->query->has('view') or 'message' == $request->query->get('view')) {
		// main message
		include $componentPath.'admin/message/newOfficialMessage.php';
		include $componentPath.'default.php';
	} elseif ('roadmap' == $request->query->get('view')) {
		// main roadmap
		include $componentPath.'admin/roadmap/addEntry.php';
		include $componentPath.'default.php';
	} else {
		$this->getContainer()->get('app.response')->redirect('404');
	}
echo '</div>';
