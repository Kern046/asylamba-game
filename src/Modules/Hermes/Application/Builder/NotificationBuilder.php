<?php

namespace App\Modules\Hermes\Application\Builder;

use App\Modules\Hermes\Model\Notification;
use App\Modules\Zeus\Model\Player;
use Symfony\Component\Uid\Uuid;

class NotificationBuilder
{
	private string $title;

	private string $content = '';

	public const RESOURCE_TYPE_CREDIT = 'credit.png';
	public const RESOURCE_TYPE_RESOURCE = 'resource.png';
	public const RESOURCE_TYPE_XP = 'xp.png';
	public const RESOURCE_TYPE_TIME = 'time.png';
	public const RESOURCE_TYPE_PEV = 'pev.png';

	public static function new(): self
	{
		return new self();
	}

	public function setTitle(string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function setContent(string ...$content): self
	{
		$this->content = implode('', $content);

		return $this;
	}

	public function addContent(string ...$content): self
	{
		$this->content .= implode('', $content);

		return $this;
	}

	public static function paragraph(string ...$content): string
	{
		return sprintf('<p>%s</p>', implode('', $content));
	}

	public static function bold($text): string
	{
		return sprintf('<strong>%s</strong>', $text);
	}

	public static function link($href, $label): string
	{
		return sprintf('<a href="%s">%s</a>', $href, $label);
	}

	public static function divider(): string
	{
		return '<hr />';
	}

	/**
	 * @param self::RESOURCE_TYPE_* $type
	 */
	public static function resourceBox(string $type, int|string $value, string $label): string
	{
		return sprintf(
			'<resource-box type="%s" value="%s" label="%s" />',
			$type,
			$value,
			$label
		);
	}

	public function for(Player $player): Notification
	{
		return new Notification(
			id: Uuid::v4(),
			player: $player,
			title: $this->title,
			content: $this->content,
			read: false,
			archived: false,
		);
	}
}
