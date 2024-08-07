<?php

declare(strict_types=1);

namespace App\Modules\Zeus\Model;

use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Resource\ColorResource;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\Uid\Uuid;

class CreditTransaction
{
	public function __construct(
		public Uuid $id,
		public Player|null $playerSender,
		public Player|null $playerReceiver,
		public Color|null $factionSender,
		public Color|null $factionReceiver,
		public int $amount,
		public \DateTimeImmutable $createdAt,
		public string|null $comment,
	) {

	}

	public function getReceiver(): CreditHolderInterface
	{
		return $this->playerReceiver ?? $this->factionReceiver;
	}

	public function getSender(): CreditHolderInterface
	{
		return $this->playerSender ?? $this->factionSender;
	}

	public function getReceiverLink(): string
	{
		return $this->getLink($this->getReceiver());
	}

	public function getSenderLink(): string
	{
		return $this->getLink($this->getSender());
	}

	/**
	 * @return string
	 */
	private function getLink(CreditHolderInterface $part): string
	{
		return match (ClassUtils::getClass($part)) {
			Color::class => '/embassy/faction-' . $part->id,
			Player::class => '/embassy/player-' . $part->id,
			default => throw new \RuntimeException('Match case not implemented'),
		};
	}

	public function getReceiverName(): string
	{
		return $this->getName($this->getReceiver());
	}

	public function getSenderName(): string
	{
		return $this->getName($this->getSender());
	}

	private function getName(CreditHolderInterface $part): string
	{
		return match (ClassUtils::getClass($part)) {
			Color::class => ColorResource::getInfo($part->identifier, 'popularName'),
			Player::class => $part->name,
			default => throw new \RuntimeException('Match case not implemented'),
		};
	}

	public function getReceiverAvatar(): string
	{
		return $this->getAvatar($this->getReceiver());
	}

	public function getSenderAvatar(): string
	{
		return $this->getAvatar($this->getSender());
	}

	private function getAvatar(CreditHolderInterface $part): string
	{
		return match (ClassUtils::getClass($part)) {
			Color::class => 'color-' . $part->identifier,
			Player::class => $part->avatar,
			default => throw new \RuntimeException('Match case not implemented'),
		};
	}

	public function getReceiverStatus(): string
	{
		return $this->getStatus($this->getReceiver());
	}

	public function getSenderStatus(): string
	{
		return $this->getStatus($this->getSender());
	}

	private function getStatus(CreditHolderInterface $part): string
	{
		return match (ClassUtils::getClass($part)) {
			Color::class => ColorResource::getInfo($part->identifier, 'government'),
			// TODO make a method to get a player status
			Player::class => ColorResource::getInfo($part->faction->identifier, 'status')[$part->status - 1],
			default => throw new \RuntimeException('Match case not implemented'),
		};
	}

	public function getReceiverColor(): int
	{
		return $this->getFaction($this->getReceiver());
	}

	public function getSenderColor(): int
	{
		return $this->getFaction($this->getSender());
	}

	private function getFaction(CreditHolderInterface $part): int
	{
		return match (ClassUtils::getClass($part)) {
			Color::class => $part->identifier,
			Player::class => $part->faction->identifier,
			default => throw new \RuntimeException('Match case not implemented'),
		};
	}
}
