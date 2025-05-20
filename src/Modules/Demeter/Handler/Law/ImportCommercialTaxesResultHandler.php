<?php

namespace App\Modules\Demeter\Handler\Law;

use App\Modules\Athena\Domain\Repository\CommercialTaxRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\ColorRepositoryInterface;
use App\Modules\Demeter\Domain\Repository\Law\LawRepositoryInterface;
use App\Modules\Demeter\Message\Law\ImportCommercialTaxesResultMessage;
use App\Modules\Demeter\Model\Law\Law;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ImportCommercialTaxesResultHandler
{
	public function __construct(
		private ColorRepositoryInterface $colorRepository,
		private CommercialTaxRepositoryInterface $commercialTaxRepository,
		private LawRepositoryInterface $lawRepository,
	) {
	}

	public function __invoke(ImportCommercialTaxesResultMessage $message): void
	{
		$law = $this->lawRepository->get($message->getLawId());
		$color = $law->faction;
		$relatedFaction = $this->colorRepository->get($law->options['rColor']);
		$tax = $this->commercialTaxRepository->getFactionsTax($color, $relatedFaction);

		$law->statement = Law::OBSOLETE;
		if ($law->options['rColor'] == $color->id) {
			$tax->exportTax = $law->options['taxes'] / 2;
			$tax->importTax = $law->options['taxes'] / 2;
		} else {
			$tax->importTax = $law->options['taxes'];
		}
		$this->lawRepository->save($law);
		$this->commercialTaxRepository->save($tax);
	}
}
