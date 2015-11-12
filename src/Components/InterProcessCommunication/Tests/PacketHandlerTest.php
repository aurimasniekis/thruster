<?php

namespace Thruster\Components\InterProcessCommunication\Tests;

use Thruster\Components\InterProcessCommunication\PacketHandler;
use Thruster\Components\InterProcessCommunication\PacketSubscriberInterface;

class PacketHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PacketHandler
     */
    protected $packetHandler;

    public function setUp()
    {
        $this->packetHandler = new PacketHandler();
    }

    public function testAddProvider()
    {
        $mock = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\PacketProviderInterface')
            ->setMethods(['onPacketReceived'])
            ->getMockForAbstractClass();

        $mock->expects($this->once())
            ->method('onPacketReceived')
            ->with([$this->packetHandler, 'receivedPackage']);

        $this->packetHandler->addProvider($mock);
    }

    public function testReceivedPackageWithoutHandler()
    {
        $packet = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Packet')
            ->getMock();

        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $packet->expects($this->once())
            ->method('setConnection')
            ->with($connection);

        $packet->expects($this->once())
            ->method('getType')
            ->willReturn('foo');

        $this->packetHandler->receivedPackage($packet, $connection);
    }

    public function testReceivedPackageWithHandler()
    {
        $packet = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Packet')
            ->getMock();

        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $packet->expects($this->once())
            ->method('setConnection')
            ->with($connection);

        $packet->expects($this->once())
            ->method('getType')
            ->willReturn('foo');

        $mock = $this->getMockBuilder(__CLASS__)
            ->getMock();

        $mock->expects($this->once())
            ->method('testReceivedPackageWithHandler')
            ->with($packet, $this->packetHandler);

        $this->packetHandler->addHandler('foo', [$mock, 'testReceivedPackageWithHandler']);
        $this->packetHandler->receivedPackage($packet, $connection);
    }

    public function testAddHasRemoveHandler()
    {
        $mock = $this->getMockBuilder(__CLASS__)
            ->getMock();

        $this->assertFalse($this->packetHandler->hasHandlers());
        $this->packetHandler->addHandler('foo', [$mock, 'testReceivedPackageWithHandler']);
        $this->assertTrue($this->packetHandler->hasHandlers());

        $this->assertEquals(
            [
                'foo' => [
                    0 => [
                        [$mock, 'testReceivedPackageWithHandler'],
                    ],
                ],
            ],
            $this->packetHandler->getHandlers(null, true)
        );

        $this->packetHandler->removeHandler('foo', [$mock, 'testReceivedPackageWithHandler']);
        $this->packetHandler->removeHandler('foo', [$mock, 'testReceivedPackageWithHandler']);
        $this->assertFalse($this->packetHandler->hasHandlers());
    }

    public function testAddAndRemoveSubscribers()
    {
        $subscriber = $this->getSubscriber();
        $this->packetHandler->addSubscriber($subscriber);

        $this->assertEquals(
            [
                'foo' => [0 => [[$subscriber, 'foo']]],
                'rab' => [0 => [[$subscriber, 'rab']]],
                'bar' => [
                    0 => [[$subscriber, 'bar']],
                    10 => [[$subscriber, 'rab']]
                ]
            ],
            $this->packetHandler->getHandlers(null, true)
        );

        $this->assertTrue($this->packetHandler->hasHandlers());
        $this->packetHandler->removeSubscriber($subscriber);
        $this->assertFalse($this->packetHandler->hasHandlers());
    }

    public function getSubscriber()
    {
        return new class implements PacketSubscriberInterface {
            /**
             * @inheritDoc
             */
            public static function getSubscribedPackets()
            {
                return [
                    'foo' => 'foo',
                    'rab' => ['rab'],
                    'bar' => [
                        ['bar'],
                        ['rab', 10]
                    ]
                ];
            }

            public function foo()
            {
            }

            public function bar()
            {
            }

            public function rab()
            {
            }
        };
    }
}
