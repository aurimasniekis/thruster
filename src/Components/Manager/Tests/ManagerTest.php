<?php

namespace Thruster\Components\Manager\Tests;

use Thruster\Components\Manager\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testSignalHandler()
    {
        $manager = new Manager();

        $this->assertInstanceOf('\Thruster\Components\PosixSignalHandler\SignalHandler', $manager->getSignalHandler());
        $this->assertInstanceOf('\Thruster\Components\PosixSignalHandler\SignalHandler', $manager->getSignalHandler());
    }

    public function testPacketHandler()
    {
        $manager = $this->getMockBuilder('\Thruster\Components\Manager\Manager')
            ->setMethods(['getConnectionManager'])
            ->getMock();

        $connectionManager = $this->getMock('\Thruster\Components\InterProcessCommunication\ConnectionManager');

        $manager->expects($this->once())
            ->method('getConnectionManager')
            ->willReturn($connectionManager);

        $this->assertInstanceOf(
            '\Thruster\Components\InterProcessCommunication\PacketHandler',
            $manager->getPacketHandler()
        );
        $this->assertInstanceOf(
            '\Thruster\Components\InterProcessCommunication\PacketHandler',
            $manager->getPacketHandler()
        );
    }

    public function testConnectionManager()
    {
        $manager = $this->getMockBuilder('\Thruster\Components\Manager\Manager')
            ->setMethods(['getSignalHandler'])
            ->getMock();

        $manager->expects($this->once())
            ->method('getSignalHandler');

        $this->assertInstanceOf(
            '\Thruster\Components\InterProcessCommunication\ConnectionManager',
            $manager->getConnectionManager()
        );
        $this->assertInstanceOf(
            '\Thruster\Components\InterProcessCommunication\ConnectionManager',
            $manager->getConnectionManager()
        );
    }

    public function testProcessExitHandler()
    {
        $manager = $this->getMockBuilder('\Thruster\Components\Manager\Manager')
            ->setMethods(['getSignalHandler'])
            ->getMock();

        $signalHandler = $this->getMock('\Thruster\Components\PosixSignalHandler\SignalHandler');

        $manager->expects($this->once())
            ->method('getSignalHandler')
            ->willReturn($signalHandler);

        $this->assertInstanceOf(
            '\Thruster\Components\ProcessExitHandler\ProcessExitHandler',
            $manager->getProcessExitHandler()
        );
        $this->assertInstanceOf(
            '\Thruster\Components\ProcessExitHandler\ProcessExitHandler',
            $manager->getProcessExitHandler()
        );
    }

    public function testNewSubTaskManager()
    {
        $manager = $this->getMockBuilder('\Thruster\Components\Manager\Manager')
            ->setMethods(['getConnectionManager', 'getProcessExitHandler', 'getPacketHandler'])
            ->getMock();

        $connectionManager = $this->getMock('\Thruster\Components\InterProcessCommunication\ConnectionManager');

        $manager->expects($this->once())
            ->method('getConnectionManager')
            ->willReturn($connectionManager);

        $processExitHandler = $this->getMock('\Thruster\Components\ProcessExitHandler\ProcessExitHandler');

        $manager->expects($this->once())
            ->method('getProcessExitHandler')
            ->willReturn($processExitHandler);

        $packetHandler = $this->getMock('\Thruster\Components\InterProcessCommunication\PacketHandler');

        $manager->expects($this->once())
            ->method('getPacketHandler')
            ->willReturn($packetHandler);

        $processExitHandler->expects($this->once())
            ->method('addHandler')
            ->willReturnCallback(
                function ($givenSubTaskManager) use ($processExitHandler) {
                    $this->assertInstanceOf('\Thruster\Components\SubTask\SubTaskManager', $givenSubTaskManager);

                    return $processExitHandler;
                }
            );

        $packetHandler->expects($this->once())
            ->method('addSubscriber')
            ->willReturnCallback(
                function ($givenSubTaskManager) use ($packetHandler) {
                    $this->assertInstanceOf('\Thruster\Components\SubTask\SubTaskManager', $givenSubTaskManager);

                    return $packetHandler;
                }
            );

        $this->assertInstanceOf('\Thruster\Components\SubTask\SubTaskManager', $manager->newSubTaskManager());
    }

    public function testSetters()
    {
        $signalHandler = $this->getMock('\Thruster\Components\PosixSignalHandler\SignalHandler');
        $packetHandler = $this->getMock('\Thruster\Components\InterProcessCommunication\PacketHandler');
        $connectionManager = $this->getMock('\Thruster\Components\InterProcessCommunication\ConnectionManager');
        $processExitHandler = $this->getMock('\Thruster\Components\ProcessExitHandler\ProcessExitHandler');

        $manager = new Manager();

        $manager->setSignalHandler($signalHandler);
        $this->assertEquals($signalHandler, $manager->getSignalHandler());

        $manager->setPacketHandler($packetHandler);
        $this->assertEquals($packetHandler, $manager->getPacketHandler());

        $manager->setConnectionManager($connectionManager);
        $this->assertEquals($connectionManager, $manager->getConnectionManager());

        $manager->setProcessExitHandler($processExitHandler);
        $this->assertEquals($processExitHandler, $manager->getProcessExitHandler());
    }
}
