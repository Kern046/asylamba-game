<?php

namespace App\Modules\Demeter\Manager\Forum;

use App\Classes\Library\Parser;
use App\Modules\Demeter\Domain\Repository\Forum\FactionNewsRepositoryInterface;
use App\Modules\Demeter\Model\Forum\FactionNews;

class FactionNewsManager
{
	public function __construct(
		private readonly FactionNewsRepositoryInterface $factionNewsRepository,
		private readonly Parser $parser,
	) {
	}

	public function edit(FactionNews $factionNews, string $content): void
	{
		$factionNews->oContent = $content;

		$this->parser->parseBigTag = true;

		$factionNews->pContent = $this->parser->parse($content);

		$this->factionNewsRepository->save($factionNews);
	}
}
