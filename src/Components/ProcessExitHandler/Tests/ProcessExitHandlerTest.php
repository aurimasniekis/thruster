<?php

namespace Thruster\Components\ProcessExitHandler\Tests;

use Thruster\Components\ProcessExitHandler\ProcessExitHandler;
use Thruster\Components\ProcessExitHandler\ProcessExitSubscriberInterface;

class ProcessExitHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $processControl;

    /**
     * @var ProcessExitHandler
     */
    protected $processExitHandler;

    public function setUp()
    {
        $this->processControl = $this->getMockBuilder('\Thruster\Wrappers\ProcessControl\ProcessControl')
            ->getMock();

        $this->processExitHandler = new ProcessExitHandler();
        $this->processExitHandler->setProcessControl($this->processControl);
    }

    public function testCheck()
    {
        $handler = $this->getHandler();
        $handler2 = $this->getHandler();

        $this->processExitHandler->addHandler($handler);
        $this->processExitHandler->addHandler($handler2);

        $handler->expects($this->once())
            ->method('onProcessExit')
            ->willReturnCallback(
                function ($processExit) {
                    $this->assertInstanceOf('\Thruster\Components\ProcessExitHandler\ProcessExit', $processExit);
                    $this->assertSame(10, $processExit->getPid());
                    $this->assertSame(10, $processExit->getStatus());

                    $processExit->stopPropagation();
                }
            );

        $this->processControl->expects($this->once())
            ->method('wait')
            ->willReturnCallback(
                function (&$status, $type) {
                    $status = 10;
                    $this->assertSame(WNOHANG, $type);

                    return 10;
                }
            );

        $this->processExitHandler->check();
    }

    public function testCheckNoExited()
    {
        $this->processControl->expects($this->once())
            ->method('wait')
            ->willReturnCallback(
                function (&$status, $type) {
                    $status = 10;
                    $this->assertSame(WNOHANG, $type);

                    return 0;
                }
            );

        $this->processExitHandler->check();
    }

    /**
     * @expectedException \Thruster\Components\ProcessExitHandler\Exception\ProcessExitHandlerException
     * @expectedExceptionMessage pcntl_wait: foo_bar
     */
    public function testCheckException()
    {
        $this->processControl->expects($this->exactly(2))
            ->method('getLastError')
            ->willReturn(1);

        $this->processControl->expects($this->once())
            ->method('getStringError')
            ->willReturn('foo_bar');

        $this->processControl->expects($this->once())
            ->method('wait')
            ->willReturnCallback(
                function (&$status, $type) {
                    $status = 10;
                    $this->assertSame(WNOHANG, $type);

                    return -1;
                }
            );

        $this->processExitHandler->check();
    }

    public function testRemoveHandler()
    {
        $handler = $this->getHandler();
        $handler2 = $this->getHandler();

        $this->processExitHandler->addHandler($handler);
        $this->processExitHandler->addHandler($handler2);

        $this->processExitHandler->removeHandler($handler);
        $expected = [1=>[$handler2, 'onProcessExit']];

        $this->assertEquals($expected, $this->processExitHandler->getHandlers());
    }

    public function testSubscribedSignals()
    {
        $this->assertEquals(
            [SIGCHLD => ['check']],
            $this->processExitHandler->getSubscribedSignals()
        );
    }

    public function getHandler()
    {
        return $this->getMockBuilder('\Thruster\Components\ProcessExitHandler\ProcessExitSubscriberInterface')
            ->getMockForAbstractClass();
    }
}
