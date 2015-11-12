<?php

namespace Thruster\Components\PosixSignalHandler\Tests;

use Thruster\Components\PosixSignalHandler\Signal;
use Thruster\Components\PosixSignalHandler\SignalHandler;
use Thruster\Components\PosixSignalHandler\SignalSubscriberInterface;

class SignalHandlerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $posix;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $processControl;

    /**
     * @var SignalHandler
     */
    protected $signalHandler;

    public function setUp()
    {
        $this->posix = $this->getMockBuilder('Thruster\Wrappers\Posix\Posix')
            ->getMock();

        $this->processControl = $this->getMockBuilder('Thruster\Wrappers\ProcessControl\ProcessControl')
            ->getMock();

        $this->signalHandler = new SignalHandler();
        $this->signalHandler->setPosix($this->posix);
        $this->signalHandler->setProcessControl($this->processControl);
    }

    public function testSend()
    {
        $signal = new Signal(9,1);

        $this->posix->expects($this->once())
            ->method('kill')
            ->with(1, 9);

        $this->signalHandler->send($signal);
    }

    public function testAddHandler()
    {
        $this->processControl->expects($this->once())
            ->method('signal')
            ->willReturnCallback(
                function ($signalNo, $callable) {
                    $this->assertSame(9, $signalNo);

                    return true;
                }
            );

        $this->signalHandler->addHandler(9, [$this, 'foo']);
        $result = $this->signalHandler->getHandlers();

        $this->assertCount(1, $result);
        $this->assertArrayHasKey(9, $result);
        $this->assertCount(1, $result[9]);
        $this->assertInstanceOf(
            '\Thruster\Components\PosixSignalHandler\Tests\SignalHandlerTest',
            $result[9][0][0]
        );
        $this->assertSame('foo', $result[9][0][1]);
    }

    public function testClearHandler()
    {
        $this->processControl->expects($this->at(0))
            ->method('signal')
            ->willReturnCallback(
                function ($signalNo, $callable) {
                    $this->assertSame(9, $signalNo);

                    return true;
                }
            );

        $this->processControl->expects($this->at(1))
            ->method('signal')
            ->with(9, SIG_DFL);

        $this->signalHandler->addHandler(9, [$this, 'foo']);
        $this->signalHandler->clearHandlers();

        $this->assertCount(0, $this->signalHandler->getHandlers());
    }

    public function testHasHandler()
    {
        $this->processControl->expects($this->at(0))
            ->method('signal')
            ->willReturnCallback(
                function ($signalNo, $callable) {
                    $this->assertSame(9, $signalNo);

                    return true;
                }
            );

        $this->assertFalse($this->signalHandler->hasHandlers(9));
        $this->signalHandler->addHandler(9, [$this, 'foo']);
        $this->assertTrue($this->signalHandler->hasHandlers(9));
    }

    public function testHandleSignal()
    {
        $mock = $this->getMockBuilder(__CLASS__)
            ->setMethods(['foo'])
            ->getMock();

        $mock->expects($this->once())
            ->method('foo')
            ->willReturnCallback(
                function ($signal, $that) {
                    $this->assertInstanceOf('\Thruster\Components\PosixSignalHandler\Signal', $signal);
                    $this->assertSame(9, $signal->getSignalNo());
                    $signal->stopPropagation();
                }
            );

        $this->signalHandler->addHandler(9, [$mock, 'foo']);
        $this->signalHandler->handleSignal(9);
    }

    public function testRemoveHandler()
    {
        $mock = $this->getMockBuilder(__CLASS__)
            ->setMethods(['foo'])
            ->getMock();

        $this->processControl->expects($this->at(1))
            ->method('signal')
            ->with(9, SIG_DFL);

        $this->signalHandler->removeHandler(9, [$mock, 'foo']);
        $this->signalHandler->addHandler(9, [$mock, 'foo']);
        $this->signalHandler->removeHandler(9, [$mock, 'foo']);
    }

    public function testAddSubscriber()
    {
        $subscriber = $this->getSubscriber();

        $this->signalHandler->addSubscriber($subscriber);

        $handlers = $this->signalHandler->getHandlers();
        $this->assertCount(3, $handlers);

        $handlers = $this->signalHandler->getHandlers(9);
        $this->assertCount(1, $handlers);
        $this->assertEquals([$subscriber, 'foo'], $handlers[0]);

        $handlers = $this->signalHandler->getHandlers(10);
        $this->assertCount(1, $handlers);
        $this->assertEquals([$subscriber, 'bar'], $handlers[0]);

        $handlers = $this->signalHandler->getHandlers(11);
        $this->assertCount(1, $handlers);
        $this->assertEquals([$subscriber, 'bar'], $handlers[0]);
    }

    public function testRemoveSubscriber()
    {
        $subscriber = $this->getSubscriber();

        $this->signalHandler->addSubscriber($subscriber);

        $handlers = $this->signalHandler->getHandlers();
        $this->assertCount(3, $handlers);

        $this->signalHandler->removeSubscriber($subscriber);

        $handlers = $this->signalHandler->getHandlers();
        $this->assertCount(0, $handlers);
    }

    public function foo()
    {

    }

    protected function getSubscriber()
    {
        return new class implements SignalSubscriberInterface {
            /**
             * @inheritDoc
             */
            public static function getSubscribedSignals() : array
            {
                return [
                    '9' => 'foo',
                    '10' => ['bar', 10],
                    '11' => [['bar', 10]]
                ];
            }

            public function foo($signal)
            {

            }

            public function bar($signal)
            {

            }
        };
    }
}
