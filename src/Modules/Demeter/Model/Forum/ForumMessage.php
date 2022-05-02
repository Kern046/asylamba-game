<?php

/**
 * Message Forum.
 *
 * @author Noé Zufferey
 * @copyright Expansion - le jeu
 *
 * @update 06.10.13
 */

namespace App\Modules\Demeter\Model\Forum;

class ForumMessage
{
	public const PUBLISHED = 1;
	public const HIDDEN = 2;
	public const FORBIDDEN_FLOOD = 3;
	public const FORBIDDEN_INSULT = 4;
	public const FORBIDDEN_PR0N = 5;
	public const FORBIDDEN_RACISM = 6;

	public $id = 0;
	public $rPlayer = 0;
	public $rTopic = 0;
	public $oContent = '';
	public $pContent = '';
	public $statement = 0;
	public $dCreation = '';
	public $dLastModification = '';

	public $playerName = '';
	public $playerColor = '';
	public $playerAvatar = '';
	public $playerStatus = '';

	public function getId()
	{
		return $this->id;
	}
}
