<?php

namespace App\Classes\Daemon;

class Client
{
    /** @var string * */
    protected $id;
    /** @var \DateTime * */
    protected $lastConnectedAt;
    /** @var bool * */
    protected $isFirstConnection;
    /** @var int * */
    protected $playerId;

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \App\Classes\Daemon\Client
     */
    public function setLastConnectedAt(\DateTime $lastConnectedAt)
    {
        $this->lastConnectedAt = $lastConnectedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastConnectedAt()
    {
        return $this->lastConnectedAt;
    }

    /**
     * @param bool $isFirstConnection
     *
     * @return $this
     */
    public function setIsFirstConnection($isFirstConnection)
    {
        $this->isFirstConnection = $isFirstConnection;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsFirstConnection()
    {
        return $this->isFirstConnection;
    }

    /**
     * @param int $playerId
     *
     * @return Client
     */
    public function setPlayerId($playerId)
    {
        $this->playerId = $playerId;

        return $this;
    }

    /**
     * @return int
     */
    public function getPlayerId()
    {
        return $this->playerId;
    }
}
