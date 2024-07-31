<?php

namespace App\Modules\Athena\Infrastructure\Controller\Financial;

use App\Modules\Zeus\Domain\Repository\CreditTransactionRepositoryInterface;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\CreditTransaction;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

class SendCreditsToFaction extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		PlayerManager $playerManager,
		CreditTransactionRepositoryInterface $creditTransactionRepository,
	): Response {
		$credit = $request->request->getInt('quantity');

		if (0 >= $credit) {
			throw new BadRequestHttpException('envoi de crédits impossible - il faut envoyer un nombre entier positif');
		}

		if ($currentPlayer->getCredits() < $credit) {
			throw new BadRequestHttpException('envoi de crédits impossible - vous ne pouvez pas envoyer plus que ce que vous possédez');
		}

		// make the transaction
		$playerManager->decreaseCredit($currentPlayer, $credit);
		$currentPlayer->faction->increaseCredit($credit);

		// create the transaction
		$ct = new CreditTransaction(
			id: Uuid::v4(),
			playerSender: $currentPlayer,
			playerReceiver: null,
			factionReceiver: $currentPlayer->faction,
			factionSender: null,
			createdAt: new \DateTimeImmutable(),
			amount: $credit,
			comment: null,
		);
		$creditTransactionRepository->save($ct);

		$this->addFlash('success', 'Crédits envoyés');

		return $this->redirectToRoute('financial_transfers');
	}
}
