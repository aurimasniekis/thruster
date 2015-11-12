<?php

namespace Thruster\Components\InterProcessCommunication\Tests;

use Thruster\Components\InterProcessCommunication\ConnectionManager;
use Thruster\Components\InterProcessCommunication\Packet;

class ConnectionManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConnectionManager
     */
    protected $connectionManager;

    public function setUp()
    {
        $this->connectionManager = new ConnectionManager();
        $this->connectionManager
            ->setSignalHandler($this->getMock('\Thruster\Components\PosixSignalHandler\SignalHandler'));
    }

    public function testDriverClass()
    {
        $this->connectionManager->setDriverClass('foo_bar');
        $this->assertSame('foo_bar', $this->connectionManager->getDriverClass());
    }

    public function testNewConnection()
    {
        $connection = $this->connectionManager->newConnection();
        $this->assertInstanceOf('\Thruster\Components\InterProcessCommunication\Connection', $connection);
        $this->assertNotNull($connection->getSubscribedSignals());

        $this->connectionManager->setDriverClass('foo/bar');
        $connection = $this->connectionManager->newConnection();
        $this->assertSame('foo/bar', $connection->getDriverClass());
    }

    public function testAddConnection()
    {
        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $connection->expects($this->once())
            ->method('setMasterMode');

        $connection->expects($this->once())
            ->method('onPacketReceived')
            ->with([$this->connectionManager, 'receivedPacket']);

        $connection->expects($this->once())
            ->method('getPid')
            ->willReturn(10);

        $this->connectionManager->addConnection($connection);
        $this->assertTrue($this->connectionManager->hasConnection(10));

        $connections = [
            10 => $connection
        ];

        $this->assertEquals($connections, $this->connectionManager->getConnections());

        $this->assertTrue($this->connectionManager->removeConnection(10));
        $this->assertFalse($this->connectionManager->hasConnection(10));
        $this->assertFalse($this->connectionManager->removeConnection(10));
    }

    public function testSendPacket()
    {
        $packet = new Packet('packet', 10);

        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $this->connectionManager->addConnection($connection);

        $connection->expects($this->once())
            ->method('sendPacket')
            ->with($packet);

        $connection->expects($this->once())
            ->method('getPid')
            ->willReturn(10);

        $this->connectionManager->sendPacket($packet);
    }

    public function testSendPacketBroadcast()
    {
        $packet = new Packet('packet', Packet::BROADCAST);

        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $this->connectionManager->addConnection($connection);

        $connection->expects($this->once())
            ->method('sendPacket')
            ->with($packet);

        $this->connectionManager->sendPacket($packet);
    }

    public function testReceivedPacket()
    {
        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $packet = new Packet('', Packet::MASTER);

        $mock = $this->getMockBuilder(__CLASS__)
            ->getMock();

        $mock->expects($this->once())
            ->method('testReceivedPacket')
            ->with($packet, $connection);

        $this->connectionManager->onPacketReceived([$mock, 'testReceivedPacket']);

        $this->connectionManager->receivedPacket($packet, $connection);
    }

    public function testReceivedPacketBroadcast()
    {
        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $packet = new Packet('', Packet::BROADCAST);

        $mock = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\ConnectionManager')
            ->setMethods(['sendPacket'])
            ->getMock();

        $mock->expects($this->once())
            ->method('sendPacket')
            ->with($packet);

        $mock->receivedPacket($packet, $connection);
    }
}
