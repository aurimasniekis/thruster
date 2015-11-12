<?php

namespace Thruster\Components\InterProcessCommunication\Tests\Driver;

use Thruster\Components\InterProcessCommunication\Driver\SocketPairDriver;

class SocketPairDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $socketPair;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $socket;

    /**
     * @var SocketPairDriver
     */
    protected $socketPairDriver;

    public function setUp()
    {
        $this->socketPair = $this->getMockBuilder('\Thruster\Components\Socket\SocketPair')
            ->getMock();

        $this->socket = $this->getMockBuilder('\Thruster\Components\Socket\Socket')
            ->getMock();

        $this->socketPairDriver = new SocketPairDriver();
        $this->socketPairDriver->getSocketPair();
        $this->socketPairDriver->setSocketPair($this->socketPair);
    }

    public function testInitializeAndDestroy()
    {
        $this->socketPair->expects($this->once())
            ->method('initialize');

        $this->socketPair->expects($this->once())
            ->method('close');

        $this->socketPairDriver->initialize();
        $this->socketPairDriver->close();
    }

    public function testSlaveMode()
    {
        $this->socketPair->expects($this->once())
            ->method('useRight');

        $this->socketPairDriver->setSlaveMode();
    }

    public function testMasterMode()
    {
        $this->socketPair->expects($this->once())
            ->method('useLeft');

        $this->socketPairDriver->setMasterMode();
    }

    public function testWrite()
    {
        $this->socketPair->expects($this->exactly(3))
            ->method('getSocket')
            ->willReturn($this->socket);

        $this->socket->expects($this->once())
            ->method('writeSelect')
            ->willReturn(1);

        $this->socket->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(
                function ($buffer) {
                    $this->assertSame(base64_decode('AAAAEnM6MTA6IjEyMzQ1Njc4OTAiOw=='), $buffer);

                    return 12;
                }
            );

        $this->socket->expects($this->at(2))
            ->method('write')
            ->willReturnCallback(
                function ($buffer) {
                    $this->assertSame(base64_decode('MzQ1Njc4OTAiOw=='), $buffer);

                    return 10;
                }
            );

        $this->socketPairDriver->write('1234567890');
    }

    public function testWriteFail()
    {
        $this->socketPair->expects($this->once())
            ->method('getSocket')
            ->willReturn($this->socket);

        $this->socket->expects($this->once())
            ->method('writeSelect')
            ->willReturn(false);

        $this->socketPairDriver->write('1234567890');
    }

    public function testRead()
    {
        $this->socketPair->expects($this->exactly(5))
            ->method('getSocket')
            ->willReturn($this->socket);

        $this->socket->expects($this->once())
            ->method('readSelect')
            ->willReturn(1);

        $this->socket->expects($this->at(1))
            ->method('read')
            ->willReturnCallback(
                function ($length) {
                    $this->assertSame(4, $length);

                    return base64_decode('AAA=');
                }
            );


        $this->socket->expects($this->at(2))
            ->method('read')
            ->willReturnCallback(
                function ($length) {
                    $this->assertSame(2, $length);

                    return base64_decode('ABI=');
                }
            );

        $this->socket->expects($this->at(3))
            ->method('read')
            ->willReturnCallback(
                function ($length) {
                    $this->assertSame(18, $length);

                    return base64_decode('czoxMDoiMTIz');
                }
            );

        $this->socket->expects($this->at(4))
            ->method('read')
            ->willReturnCallback(
                function ($length) {
                    $this->assertSame(9, $length);

                    return base64_decode('NDU2Nzg5MCI7');
                }
            );

        $this->assertSame('1234567890', $this->socketPairDriver->read());
    }

    public function testReadEmptyHeader()
    {
        $this->socketPair->expects($this->exactly(4))
            ->method('getSocket')
            ->willReturn($this->socket);

        $this->socket->expects($this->once())
            ->method('readSelect')
            ->willReturn(1);

        $this->socket->expects($this->at(1))
            ->method('read')
            ->willReturnCallback(
                function ($length) {
                    $this->assertSame(4, $length);

                    return base64_decode('AAA=');
                }
            );


        $this->socket->expects($this->at(2))
            ->method('read')
            ->willReturnCallback(
                function ($length) {
                    $this->assertSame(2, $length);

                    return base64_decode('ABI=');
                }
            );


        $this->socket->expects($this->at(3))
            ->method('read')
            ->willReturnCallback(
                function ($length) {
                    $this->assertSame(18, $length);

                    return '';
                }
            );

        $this->assertNull($this->socketPairDriver->read());
    }

    public function testReadEmptyBody()
    {
        $this->socketPair->expects($this->exactly(2))
            ->method('getSocket')
            ->willReturn($this->socket);

        $this->socket->expects($this->once())
            ->method('readSelect')
            ->willReturn(1);

        $this->socket->expects($this->at(1))
            ->method('read')
            ->willReturnCallback(
                function ($length) {
                    $this->assertSame(4, $length);

                    return '';
                }
            );

        $this->assertNull($this->socketPairDriver->read());
    }

    public function testReadFail()
    {
        $this->socketPair->expects($this->once())
            ->method('getSocket')
            ->willReturn($this->socket);

        $this->socket->expects($this->once())
            ->method('readSelect')
            ->willReturn(false);

        $this->socketPairDriver->read();
    }
}
