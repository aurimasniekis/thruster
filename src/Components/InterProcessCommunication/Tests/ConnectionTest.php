<?php

namespace Thruster\Components\InterProcessCommunication\Tests;

use Thruster\Components\InterProcessCommunication\Connection;
use Thruster\Components\InterProcessCommunication\Packet;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $driver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $signalHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $posix;

    /**
     * @var Connection
     */
    protected $connection;

    public function setUp()
    {
        $this->driver = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Driver\DriverInterface')
            ->getMockForAbstractClass();

        $this->signalHandler = $this->getMockBuilder('\Thruster\Components\PosixSignalHandler\SignalHandler')
            ->getMock();

        $this->posix = $this->getMockBuilder('\Thruster\Wrappers\Posix\Posix')
            ->getMock();

        $this->connection = new Connection();
        $this->connection->setSignalHandler($this->signalHandler);
        $this->connection->setPosix($this->posix);
        $this->connection->getDriver();
        $this->connection->setDriver($this->driver);
    }

    public function testInitializeAndClose()
    {
        $this->driver->expects($this->once())
            ->method('initialize');

        $this->driver->expects($this->once())
            ->method('close');

        $this->signalHandler->expects($this->once())
            ->method('addSubscriber')
            ->with($this->connection);

        $this->signalHandler->expects($this->once())
            ->method('removeSubscriber')
            ->with($this->connection);

        $this->connection->initialize();
        $this->connection->close();
    }

    public function testSendPacketAsMaster()
    {
        $packet = new Packet();

        $this->driver->expects($this->once())
            ->method('write')
            ->with($packet);

        $this->posix->expects($this->once())
            ->method('getPid')
            ->willReturn(10);

        $this->signalHandler->expects($this->once())
            ->method('send')
            ->willReturnCallback(
                function ($signal) {
                    $this->assertInstanceOf('\Thruster\Components\PosixSignalHandler\Signal', $signal);
                    $this->assertSame(SIGUSR1, $signal->getSignalNo());
                    $this->assertSame(10, $signal->getDestination());

                    return $this->signalHandler;
                }
            );

        $this->connection->setMasterMode();
        $this->connection->sendPacket($packet);
    }

    public function testSendPacketAsSlave()
    {
        $packet = new Packet();

        $this->driver->expects($this->once())
            ->method('write')
            ->with($packet);

        $this->posix->expects($this->once())
            ->method('getParentPid')
            ->willReturn(10);

        $this->signalHandler->expects($this->once())
            ->method('send')
            ->willReturnCallback(
                function ($signal) {
                    $this->assertInstanceOf('\Thruster\Components\PosixSignalHandler\Signal', $signal);
                    $this->assertSame(SIGUSR1, $signal->getSignalNo());
                    $this->assertSame(10, $signal->getDestination());

                    return $this->signalHandler;
                }
            );

        $this->connection->setSlaveMode();
        $this->connection->sendPacket($packet);

        $this->assertSame(10, $this->connection->getParentPid());
    }

    public function testReceivePacket()
    {
        $packet = new Packet();
        $mock = $this->getMockBuilder(__CLASS__)
            ->getMock();

        $mock->expects($this->once())
            ->method('testReceivePacket')
            ->willReturnCallback(
                function ($givenPacket) use ($packet) {
                    $this->assertEquals($packet, $givenPacket);
                }
            );

        $this->driver->expects($this->once())
            ->method('read')
            ->willReturn($packet);

        $this->connection->onPacketReceived([$mock, 'testReceivePacket']);
        $this->connection->receivePacket();
    }

    public function testSubscribedSignals()
    {
        $this->assertEquals(
            [
                SIGUSR1 => ['receivePacket']
            ],
            $this->connection->getSubscribedSignals()
        );
    }

    public function testPidSetters()
    {
        $this->connection->setParentPid(10);
        $this->assertSame(10, $this->connection->getParentPid());

        $this->connection->setPid(10);
        $this->assertSame(10, $this->connection->getPid());
    }
}
