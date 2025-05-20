<?php

namespace App\Modules\Demeter\Manager\Forum;

use App\Classes\Library\Parser;
use App\Modules\Demeter\Model\Forum\ForumMessage;

class ForumMessageManager
{
	public function __construct(
		private readonly Parser $parser,
	) {
	}

	public function edit(ForumMessage $message, string $content): void
	{
		$message->oContent = $content;

		$this->parser->parseBigTag = true;

		$message->pContent = $this->parser->parse($content);
	}
}
