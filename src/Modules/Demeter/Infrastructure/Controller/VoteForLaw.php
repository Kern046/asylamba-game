<?php

namespace App\Modules\Demeter\Infrastructure\Controller;

use App\Classes\Exception\ErrorException;
use App\Classes\Library\Utils;
use App\Modules\Demeter\Manager\Law\LawManager;
use App\Modules\Demeter\Manager\Law\VoteLawManager;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Model\Law\VoteLaw;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VoteForLaw extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		LawManager $lawManager,
		VoteLawManager $voteLawManager,
		int $id,
	): Response {
		$choice = $request->query->get('choice');

		if ($choice !== FALSE) {
			if ($currentPlayer->isSenator()) {

				if (($law = $lawManager->get($id)) !== null) {
					if ($law->statement == Law::VOTATION) {
						if ($voteLawManager->hasVoted($currentPlayer->getId(), $law)) {
							throw new ErrorException('Vous avez déjà voté.');
						}
						$vote = new VoteLaw();
						$vote->rPlayer = $currentPlayer->getId();
						$vote->rLaw = $id;
						$vote->vote = $choice;
						$vote->dVotation = Utils::now();
						$voteLawManager->add($vote);

						return $this->redirect($request->headers->get('referer'));
					} else {
						throw new ErrorException('Cette loi est déjà votée.');
					}
				} else {
					throw new ErrorException('Cette loi n\'existe pas.');
				}
			} else {
				throw new ErrorException('Vous n\'avez pas le droit de voter.');
			}
		} else {
			throw new ErrorException('Informations manquantes.');
		}
	}
}
