<?php

namespace Thruster\Components\InterProcessCommunication;

use Thruster\Components\InterProcessCommunication\Driver\DriverInterface;
use Thruster\Components\PosixSignalHandler\Signal;
use Thruster\Components\PosixSignalHandler\SignalHandlerTrait;
use Thruster\Components\PosixSignalHandler\SignalSubscriberInterface;
use Thruster\Wrappers\Posix\PosixTrait;

/**
 * Class Connection
 *
 * @package Thruster\Components\InterProcessCommunication
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Connection implements SignalSubscriberInterface, PacketProviderInterface
{
    use SignalHandlerTrait;
    use PosixTrait;

    const DEFAULT_DRIVER = 'Thruster\\Components\\InterProcessCommunication\\Driver\\SocketPairDriver';

    /**
     * @var string
     */
    protected $driverClass;

    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var bool
     */
    protected $slave;

    /**
     * @var int
     */
    protected $pid;

    /**
     * @var int
     */
    protected $parentPid;

    /**
     * @var callable
     */
    protected $receivedCallback;

    /**
     * Connection constructor.
     *
     * @param string $driverClass
     */
    public function __construct($driverClass = self::DEFAULT_DRIVER)
    {
        $this->driverClass = $driverClass;
        $this->slave = false;
    }

    /**
     * @return $this
     */
    public function initialize() : self
    {
        $this->getDriver()->initialize();
        $this->getSignalHandler()->addSubscriber($this);

        return $this;
    }

    /**
     * @return $this
     */
    public function close() : self
    {
        $this->getDriver()->close();
        $this->getSignalHandler()->removeSubscriber($this);

        return $this;
    }

    public function sendPacket(Packet $packet)
    {
        if (null === $packet->getFrom()) {
            $packet->setFrom($this->getPid());
        }

        $this->getDriver()->write($packet);

        $destination = $this->isSlave() ? $this->getParentPid() : $this->getPid();

        $signal = new Signal(SIGUSR1, $destination);
        $this->getSignalHandler()->send($signal);
    }

    public function receivePacket()
    {
        $packet = $this->getDriver()->read();

        if ('' !== $packet && null !== $packet) {
            call_user_func($this->receivedCallback, $packet, $this);
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedSignals() : array
    {
        return [
            SIGUSR1 => ['receivePacket']
        ];
    }

    /**
     * @return string
     */
    public function getDriverClass()
    {
        return $this->driverClass;
    }

    /**
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface
    {
        if ($this->driver) {
            return $this->driver;
        }

        $driverClass = $this->getDriverClass();
        $this->driver = new $driverClass;

        return $this->driver;
    }

    /**
     * @param DriverInterface $driver
     *
     * @return $this
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSlave(): bool
    {
        return $this->slave;
    }

    /**
     * @return $this
     */
    public function setMasterMode() : self
    {
        $this->getDriver()->setMasterMode();

        return $this;
    }

    /**
     * @return $this
     */
    public function setSlaveMode() : self
    {
        $this->getDriver()->setSlaveMode();

        $this->slave = true;

        return $this;
    }

    /**
     * @return int
     */
    public function getPid() : int
    {
        if ($this->pid) {
            return $this->pid;
        }

        $this->pid = $this->getPosix()->getPid();

        return $this->pid;
    }

    /**
     * @param int $pid
     *
     * @return $this
     */
    public function setPid(int $pid) : self
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * @return int
     */
    public function getParentPid() : int
    {
        if ($this->parentPid) {
            return $this->parentPid;
        }

        $this->parentPid = $this->getPosix()->getParentPid();

        return $this->parentPid;
    }

    /**
     * @param int $parentPid
     *
     * @return $this
     */
    public function setParentPid(int $parentPid) : self
    {
        $this->parentPid = $parentPid;

        return $this;
    }

    /**
     * @param callable $callback
     */
    public function onPacketReceived($callback)
    {
        $this->receivedCallback = $callback;
    }
}
