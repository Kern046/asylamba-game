<?php

use App\Classes\Exception\ErrorException;
use App\Classes\Exception\FormException;
use App\Classes\Library\Flashbag;
use App\Classes\Library\Utils;

$request = $this->getContainer()->get('app.request');
$session = $this->getContainer()->get(\App\Classes\Library\Session\SessionWrapper::class);
$topicManager = $this->getContainer()->get(\App\Modules\Demeter\Manager\Forum\ForumTopicManager::class);
$forumMessageManager = $this->getContainer()->get(\App\Modules\Demeter\Manager\Forum\ForumMessageManager::class);

$content = $request->request->get('content');
$id = $request->query->get('id');

if ($content && $id) {
	$_FMM = $forumMessageManager->getCurrentSession();
	$forumMessageManager->newSession();
	$forumMessageManager->load(['id' => $id]);

	if ($forumMessageManager->size() > 0) {
		$m = $forumMessageManager->get();

		$_TOM = $topicManager->getCurrentSession();
		$topicManager->newSession();
		$topicManager->load(['id' => $m->rTopic]);

		$t = $topicManager->get();

		if ($session->get('playerId') == $m->rPlayer || ($session->get('playerInfo')->get('status') > 2 && 20 != $t->rForum)) {
			$forumMessageManager->edit($m, $content);
			$m->dLastModification = Utils::now();

			$session->addFlashbag('Message édité.', Flashbag::TYPE_SUCCESS);
		} else {
			throw new ErrorException('Vous ne pouvez pas éditer ce message.');
		}

		$topicManager->changeSession($_TOM);
	} else {
		throw new ErrorException('Le message n\'existe pas.');
	}

	$forumMessageManager->changeSession($_FMM);
} else {
	throw new FormException('Manque d\'information.');
}
