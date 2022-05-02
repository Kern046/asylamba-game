<?php

namespace App\Classes\Library\Session;

use App\Classes\Redis\RedisManager;

class SessionWrapper
{
	protected ?Session $currentSession = null;
	protected RedisManager $redisManager;

	public function __construct(RedisManager $redisManager)
	{
		$this->redisManager = $redisManager;
	}

	public function setCurrentSession(Session $session)
	{
		$this->currentSession = $session;
	}

	public function getCurrentSession(): Session
	{
		return $this->currentSession;
	}

	/**
	 * @param int $sessionId
	 *
	 * @return Session
	 */
	public function createSession($sessionId)
	{
		$session = new Session();
		$session->add('session_id', $sessionId);

		$this->save($session);

		return $session;
	}

	public function save(Session $session)
	{
		$redisConnection = $this->redisManager->getConnection();
		$redisConnection->set('session:'.$session->get('session_id'), serialize($session->getData()));
	}

	/**
	 * @param string $sessionId
	 *
	 * @return \App\Classes\Library\Session\Session
	 */
	public function fetchSession($sessionId)
	{
		if (($data = $this->redisManager->getConnection()->get('session:'.$sessionId)) === false) {
			return null;
		}
		$session = new Session();
		$session->setData(unserialize($data));

		return $session;
	}

	public function clearWrapper()
	{
		if (null !== $this->currentSession) {
			$this->save($this->currentSession);
		}
		$this->currentSession = null;
	}

	public function add($key, $value): void
	{
		if (null === $this->currentSession) {
			return;
		}
		$this->currentSession->add($key, $value);
	}

	public function addBase($key, $id, $name, $sector, $system, $img, $type): bool
	{
		if (null === $this->currentSession) {
			return false;
		}

		return $this->currentSession->addBase($key, $id, $name, $sector, $system, $img, $type);
	}

	public function addFlashbag($message, $type)
	{
		if (null === $this->currentSession) {
			return;
		}
		$this->currentSession->addFlashbag($message, $type);
	}

	public function addHistory($path)
	{
		if (null === $this->currentSession) {
			return null;
		}

		return $this->currentSession->addHistory($path);
	}

	public function all()
	{
		if (null === $this->currentSession) {
			return [];
		}

		return $this->currentSession->all();
	}

	public function baseExist($id)
	{
		if (null === $this->currentSession) {
			return false;
		}

		return $this->currentSession->baseExist($id);
	}

	public function clear()
	{
		if (null === $this->currentSession) {
			return null;
		}

		return $this->currentSession->clear();
	}

	public function destroy()
	{
		if (null === $this->currentSession) {
			return null;
		}

		return $this->currentSession->destroy();
	}

	public function exist($key)
	{
		if (null === $this->currentSession) {
			return false;
		}

		return $this->currentSession->exist($key);
	}

	public function flushFlashbags(): void
	{
		if (null === $this->currentSession) {
			return;
		}
		$this->currentSession->flushFlashbags();
	}

	public function getFlashbags(): array
	{
		if (null === $this->currentSession) {
			return [];
		}

		return $this->currentSession->getFlashbags();
	}

	public function getHistory()
	{
		if (null === $this->currentSession) {
			return [];
		}

		return $this->currentSession->getHistory();
	}

	public function getLastHistory()
	{
		if (null === $this->currentSession) {
			return null;
		}

		return $this->currentSession->getLastHistory();
	}

	public function getLifetime()
	{
		if (null === $this->currentSession) {
			return 0;
		}

		return $this->currentSession->getLifetime();
	}

	public function initLastUpdate()
	{
		if (null === $this->currentSession) {
			return null;
		}

		return $this->currentSession->initLastUpdate();
	}

	public function initPlayerBase()
	{
		if (null === $this->currentSession) {
			return null;
		}

		return $this->currentSession->initPlayerBase();
	}

	public function initPlayerBonus()
	{
		if (null === $this->currentSession) {
			return null;
		}

		return $this->currentSession->initPlayerBonus();
	}

	public function initPlayerEvent()
	{
		if (null === $this->currentSession) {
			return null;
		}

		return $this->currentSession->initPlayerEvent();
	}

	public function initPlayerInfo()
	{
		if (null === $this->currentSession) {
			return null;
		}

		return $this->currentSession->initPlayerInfo();
	}

	public function remove($key)
	{
		if (null === $this->currentSession) {
			return null;
		}

		return $this->currentSession->remove($key);
	}

	public function removeBase($key, $id)
	{
		if (null === $this->currentSession) {
			return null;
		}

		return $this->currentSession->removeBase($key, $id);
	}

	public function get($key)
	{
		if (null === $this->currentSession) {
			return null;
		}

		return $this->currentSession->get($key);
	}
}
