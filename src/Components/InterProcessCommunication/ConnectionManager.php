<?php

namespace Thruster\Components\InterProcessCommunication;

use Thruster\Components\PosixSignalHandler\SignalHandlerTrait;

/**
 * Class ConnectionManager
 *
 * @package Thruster\Components\InterProcessCommunication
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class ConnectionManager implements PacketProviderInterface
{
    use SignalHandlerTrait;

    /**
     * @var string
     */
    protected $driverClass;

    /**
     * @var Connection[]
     */
    protected $connections;

    /**
     * @var callable
     */
    protected $receivedCallback;

    public function __construct()
    {
        $this->connections = [];
    }

    /**
     * Initiates a new connection with set signalHandler
     *
     * @return Connection
     */
    public function newConnection(): Connection
    {
        // If driver class was set for connectionManager when all new Connections will use the same driver class
        // otherwise we use default one
        $driverClass = $this->getDriverClass();
        if (null !== $driverClass) {
            $connection = new Connection($driverClass);
        } else {
            $connection = new Connection();
        }

        $connection->setSignalHandler($this->getSignalHandler());

        return $connection;
    }

    /**
     * Adds connection to list and sets connection to master mode
     *
     * @param Connection $connection
     *
     * @return $this
     */
    public function addConnection(Connection $connection)
    {
        $connection->setMasterMode();
        $connection->onPacketReceived([$this, 'receivedPacket']);

        $this->connections[$connection->getPid()] = $connection;

        return $this;
    }

    /**
     * @param int $pid
     *
     * @return bool
     */
    public function hasConnection(int $pid): bool
    {
        return array_key_exists($pid, $this->connections);
    }

    /**
     * @param int $pid
     *
     * @return bool
     */
    public function removeConnection(int $pid): bool
    {
        if ($this->hasConnection($pid)) {
            unset($this->connections[$pid]);

            return true;
        }

        return false;
    }

    /**
     * @param Packet $packet
     * @param Connection $connection
     */
    public function receivedPacket($packet, $connection)
    {
        if (Packet::BROADCAST === $packet->getDestination() ||
            (
                null !== $packet->getDestination() &&
                Packet::MASTER < $packet->getDestination()
            )
        ) {
            $this->sendPacket($packet);
        }

        if (Packet::MASTER === $packet->getDestination() ||
            null === $packet->getDestination()
        ) {
            call_user_func(
                $this->receivedCallback,
                $packet,
                $connection
            );
        }
    }

    /**
     * @param Packet $packet
     */
    public function sendPacket(Packet $packet)
    {
        if (Packet::BROADCAST === $packet->getDestination()) {
            foreach ($this->getConnections() as $connection) {
                $connection->sendPacket($packet);
            }
        } else {
            foreach ($this->getConnections() as $connection) {
                if ($packet->getDestination() === $connection->getPid()) {
                    $connection->sendPacket($packet);
                }
            }
        }
    }

    public function onPacketReceived($callback)
    {
        $this->receivedCallback = $callback;
    }

    /**
     * @return string
     */
    public function getDriverClass()
    {
        return $this->driverClass;
    }

    /**
     * @param string $driverClass
     */
    public function setDriverClass($driverClass)
    {
        $this->driverClass = $driverClass;
    }

    /**
     * @return Connection[]
     */
    public function getConnections(): array
    {
        return $this->connections;
    }
}
