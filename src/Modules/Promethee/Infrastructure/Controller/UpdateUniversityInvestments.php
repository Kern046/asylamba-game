<?php

namespace App\Modules\Promethee\Infrastructure\Controller;

use App\Classes\Entity\EntityManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UpdateUniversityInvestments extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		EntityManager $entityManager,
		string $category,
		string $operation,
	): Response {
		$payload = $request->toArray();

		if (!array_key_exists('quantity', $payload)) {
			throw new BadRequestHttpException('Missing quantity');
		}
		if (!is_int($payload['quantity'])) {
			throw new BadRequestHttpException(sprintf('Invalid quantity, expected int, got %s', $payload['quantity']));
		}

		if ('increase' === $operation) {
			$this->increaseInvestment($currentPlayer, $category, $payload['quantity']);
		} else {
			$this->decreaseInvestment($currentPlayer, $category, $payload['quantity']);
		}

		$entityManager->flush($currentPlayer);

		return new Response('', Response::HTTP_NO_CONTENT);
	}

	private function increaseInvestment(Player $player, string $category, int $quantity): void
	{
		$totalInvest =
			$player->partNaturalSciences +
			$player->partLifeSciences +
			$player->partSocialPoliticalSciences +
			$player->partInformaticEngineering;

		if ($totalInvest < 100) {
			if ($totalInvest + $quantity > 100) {
				$payload['quantity'] = 100 - $totalInvest;
			}

			switch ($category) {
				case 'natural':
					$player->partNaturalSciences = $player->partNaturalSciences + $quantity;
					break;
				case 'life':
					$player->partLifeSciences = $player->partLifeSciences + $quantity;
					break;
				case 'social':
					$player->partSocialPoliticalSciences = $player->partSocialPoliticalSciences + $quantity;
					break;
				case 'informatic':
					$player->partInformaticEngineering = $player->partInformaticEngineering + $quantity;
					break;
			}
		}
	}

	private function decreaseInvestment(Player $player, string $category, int $quantity): void
	{
		if (0 === $quantity) {
			$quantity = 1;
		}

		$oldInvest = match ($category) {
			'natural' => $player->partNaturalSciences,
			'life' => $player->partLifeSciences,
			'social' => $player->partSocialPoliticalSciences,
			'informatic' => $player->partInformaticEngineering,
		};

		if (0 != $oldInvest) {
			if ($oldInvest < $quantity) {
				$quantity = $oldInvest;
			}
			switch ($category) {
				case 'natural':
					$player->partNaturalSciences = $player->partNaturalSciences - $quantity;
					break;
				case 'life':
					$player->partLifeSciences = $player->partLifeSciences - $quantity;
					break;
				case 'social':
					$player->partSocialPoliticalSciences = $player->partSocialPoliticalSciences - $quantity;
					break;
				case 'informatic':
					$player->partInformaticEngineering = $player->partInformaticEngineering - $quantity;
					break;
			}
		}
	}
}
