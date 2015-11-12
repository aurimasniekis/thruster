<?php

namespace Thruster\Components\Socket\Tests;

use Thruster\Components\Socket\SocketPair;

class SocketPairTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $socketLib;

    /**
     * @var SocketPair
     */
    protected $socketPair;

    public function setUp()
    {
        $this->socketLib = $this->getMockBuilder('Thruster\Wrappers\SocketLib\SocketLib')
            ->getMock();

        $this->socketPair = new SocketPair();
        $this->socketPair->setSocketLib($this->socketLib);
    }

    public function testInitialize()
    {
        $this->socketLib->expects($this->once())
            ->method('createPair')
            ->willReturnCallback(
                function ($domain, $type, $protocol, &$fd) {
                    $this->assertSame(AF_UNIX, $domain);
                    $this->assertSame(SOCK_STREAM, $type);
                    $this->assertSame(0, $protocol);
                    $this->assertSame([], $fd);

                    $fd = ['left', 'right'];

                    return true;
                }
            );

        $this->socketPair->initialize();

        $this->assertInstanceOf('\Thruster\Components\Socket\Socket', $this->socketPair->getLeft());
        $this->assertInstanceOf('\Thruster\Components\Socket\Socket', $this->socketPair->getRight());

        $this->assertSame('left', $this->socketPair->getLeft()->getSocket());
        $this->assertSame('right', $this->socketPair->getRight()->getSocket());
    }

    /**
     * @expectedException \Thruster\Components\Socket\Exception\SocketPairException
     * @expectedExceptionMessage foo_bar
     */
    public function testInitializeFalse()
    {
        $this->socketLib->expects($this->once())
            ->method('createPair')
            ->willReturn(false);

        $this->socketLib->expects($this->once())
            ->method('stringError')
            ->willReturn('foo_bar');

        $this->socketPair->initialize();
    }

    public function testUseLeft()
    {
        $left = $this->getSocketMock();
        $right = $this->getSocketMock();

        $right->expects($this->once())
            ->method('close');

        $this->socketPair->setLeft($left);
        $this->socketPair->setRight($right);

        $this->socketPair->useLeft();

        $this->assertNull($this->socketPair->getRight());
        $this->assertEquals($left, $this->socketPair->getSocket());
    }

    public function testUseRight()
    {
        $left = $this->getSocketMock();
        $right = $this->getSocketMock();

        $left->expects($this->once())
            ->method('close');

        $this->socketPair->setLeft($left);
        $this->socketPair->setRight($right);

        $this->socketPair->useRight();

        $this->assertNull($this->socketPair->getLeft());
        $this->assertEquals($right, $this->socketPair->getSocket());
    }

    public function testClose()
    {
        $left = $this->getSocketMock();
        $right = $this->getSocketMock();

        $right->expects($this->once())
            ->method('close');

        $left->expects($this->once())
            ->method('close');

        $this->socketPair->setLeft($left);
        $this->socketPair->setRight($right);

        $this->socketPair->close();
    }

    public function getSocketMock()
    {
        return $this->getMockBuilder('\Thruster\Components\Socket\Socket')
            ->setMethods(['close'])
            ->getMock();
    }
}
