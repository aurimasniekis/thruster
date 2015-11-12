<?php

namespace Thruster\Components\Socket\Tests;

use Thruster\Components\Socket\Socket;

class SocketTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $socketLib;

    /**
     * @var Socket
     */
    protected $socket;

    /**
     * @var resource
     */
    protected $resource;

    public function setUp()
    {
        $this->socketLib = $this->getMockBuilder('Thruster\Wrappers\SocketLib\SocketLib')
                                ->getMock();

        $this->resource = tmpfile();

        $this->socket = new Socket();
        $this->socket->setSocketLib($this->socketLib);
        $this->socket->setSocket($this->resource);
    }

    public function testClose()
    {
        $this->socketLib->expects($this->once())
            ->method('close')
            ->with($this->resource);

        $this->assertTrue($this->socket->close());
    }

    public function testCloseNotExists()
    {
        $this->socket->setSocket(null);

        $this->assertFalse($this->socket->close());
    }

    public function testSelect()
    {
        $nothing = ['foo'];

        $this->socketLib->expects($this->once())
            ->method('select')
            ->willReturnCallback(
                function ($read, $write, $expect, $timeoutSec, $timeoutMicroSec) use ($nothing) {
                    $this->assertEquals($nothing, $read);
                    $this->assertEquals($nothing, $write);
                    $this->assertEquals($nothing, $expect);
                    $this->assertSame(10, $timeoutSec);
                    $this->assertSame(100, $timeoutMicroSec);

                    return 1;
                }
            );

        $this->assertSame(1, $this->socket->select($nothing, $nothing, $nothing, 10, 100));
    }

    /**
     * @expectedException \Thruster\Components\Socket\Exception\SocketException
     * @expectedExceptionMessage select: foo_bar
     */
    public function testSelectFail()
    {
        $nothing = ['foo'];

        $this->socketLib->expects($this->once())
            ->method('select')
            ->willReturn(false);

        $this->socketLib->expects($this->once())
            ->method('stringError')
            ->willReturn('foo_bar');

        $this->assertSame(1, $this->socket->select($nothing, $nothing, $nothing, 10, 100));
    }

    public function testWriteSelect()
    {
        $this->socketLib->expects($this->once())
            ->method('select')
            ->willReturnCallback(
                function ($read, $write, $expect, $timeoutSec, $timeoutMicroSec) {
                    $this->assertSame([], $read);
                    $this->assertEquals([$this->resource], $write);
                    $this->assertSame([], $expect);
                    $this->assertSame(10, $timeoutSec);
                    $this->assertSame(100, $timeoutMicroSec);

                    return 1;
                }
            );

        $this->assertTrue($this->socket->writeSelect(10, 100));
    }

    public function testReadSelect()
    {
        $this->socketLib->expects($this->once())
            ->method('select')
            ->willReturnCallback(
                function ($read, $write, $expect, $timeoutSec, $timeoutMicroSec) {
                    $this->assertEquals([$this->resource], $read);
                    $this->assertSame([], $write);
                    $this->assertSame([], $expect);
                    $this->assertSame(10, $timeoutSec);
                    $this->assertSame(100, $timeoutMicroSec);

                    return 1;
                }
            );

        $this->assertTrue($this->socket->readSelect(10, 100));
    }

    public function testWrite()
    {
        $this->socketLib->expects($this->once())
            ->method('write')
            ->willReturnCallback(
                function ($socket, $buffer, $length) {
                    $this->assertEquals($this->resource, $socket);
                    $this->assertSame('buffer', $buffer);
                    $this->assertSame(10, $length);

                    return true;
                }
            );

        $this->socket->write('buffer', 10);
    }

    /**
     * @expectedException \Thruster\Components\Socket\Exception\SocketException
     * @expectedExceptionMessage write: foo_bar
     */
    public function testWriteFail()
    {
        $this->socketLib->expects($this->once())
            ->method('write')
            ->willReturn(false);

        $this->socketLib->expects($this->once())
            ->method('stringError')
            ->willReturn('foo_bar');

        $this->socket->write('buffer', 10);
    }

    public function testRead()
    {
        $this->socketLib->expects($this->once())
            ->method('read')
            ->willReturnCallback(
                function ($socket, $length) {
                    $this->assertEquals($this->resource, $socket);
                    $this->assertSame(10, $length);

                    return 'buffer';
                }
            );

        $this->assertSame('buffer', $this->socket->read(10));
    }

    /**
     * @expectedException \Thruster\Components\Socket\Exception\SocketException
     * @expectedExceptionMessage read: foo_bar
     */
    public function testReadFail()
    {
        $this->socketLib->expects($this->once())
            ->method('read')
            ->willReturn(false);

        $this->socketLib->expects($this->once())
            ->method('stringError')
            ->willReturn('foo_bar');

        $this->socket->read(10);
    }
}
