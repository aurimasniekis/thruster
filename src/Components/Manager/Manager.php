<?php

namespace Thruster\Components\Manager;

use Thruster\Components\InterProcessCommunication\ConnectionManager;
use Thruster\Components\InterProcessCommunication\PacketHandler;
use Thruster\Components\PosixSignalHandler\SignalHandler;
use Thruster\Components\ProcessExitHandler\ProcessExitHandler;
use Thruster\Components\SubTask\SubTaskManager;

/**
 * Class Manager
 *
 * @package Thruster\Components\Manager
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Manager
{
    /**
     * @var SignalHandler
     */
    protected $signalHandler;

    /**
     * @var PacketHandler
     */
    protected $packetHandler;

    /**
     * @var ConnectionManager
     */
    protected $connectionManager;

    /**
     * @var ProcessExitHandler
     */
    protected $processExitHandler;

    /**
     * @return SignalHandler
     */
    public function getSignalHandler()
    {
        if ($this->signalHandler) {
            return $this->signalHandler;
        }

        $this->signalHandler = new SignalHandler();

        return $this->signalHandler;
    }

    /**
     * @param SignalHandler $signalHandler
     *
     * @return $this
     */
    public function setSignalHandler($signalHandler)
    {
        $this->signalHandler = $signalHandler;

        return $this;
    }

    /**
     * @return PacketHandler
     */
    public function getPacketHandler()
    {
        if ($this->packetHandler) {
            return $this->packetHandler;
        }

        $this->packetHandler = new PacketHandler();
        $this->packetHandler->addProvider($this->getConnectionManager());

        return $this->packetHandler;
    }

    /**
     * @param PacketHandler $packetHandler
     *
     * @return $this
     */
    public function setPacketHandler($packetHandler)
    {
        $this->packetHandler = $packetHandler;

        return $this;
    }

    /**
     * @return ConnectionManager
     */
    public function getConnectionManager()
    {
        if ($this->connectionManager) {
            return $this->connectionManager;
        }

        $this->connectionManager = new ConnectionManager();
        $this->connectionManager->setSignalHandler($this->getSignalHandler());

        return $this->connectionManager;
    }

    /**
     * @param ConnectionManager $connectionManager
     *
     * @return $this
     */
    public function setConnectionManager($connectionManager)
    {
        $this->connectionManager = $connectionManager;

        return $this;
    }

    /**
     * @return ProcessExitHandler
     */
    public function getProcessExitHandler()
    {
        if ($this->processExitHandler) {
            return $this->processExitHandler;
        }

        $this->processExitHandler = new ProcessExitHandler();
        $this->getSignalHandler()->addSubscriber($this->processExitHandler);

        return $this->processExitHandler;
    }

    /**
     * @param ProcessExitHandler $processExitHandler
     *
     * @return $this
     */
    public function setProcessExitHandler($processExitHandler)
    {
        $this->processExitHandler = $processExitHandler;

        return $this;
    }

    /**
     * @return SubTaskManager
     */
    public function newSubTaskManager() : SubTaskManager
    {
        $subTaskManager = new SubTaskManager($this->getConnectionManager());

        $this->getProcessExitHandler()->addHandler($subTaskManager);
        $this->getPacketHandler()->addSubscriber($subTaskManager);

        return $subTaskManager;
    }
}
