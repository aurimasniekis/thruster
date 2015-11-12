<?php

namespace Thruster\Components\ProcessExitHandler\Tests;

use Thruster\Components\ProcessExitHandler\ProcessExit;

class ProcessExitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $processControl;

    public function setUp()
    {
        $this->processControl = $this->getMockBuilder('\Thruster\Wrappers\ProcessControl\ProcessControl')
            ->getMock();
    }

    /**
     * @param int $pid
     * @param int $status
     *
     * @return ProcessExit
     */
    public function getProcessExit($pid, $status)
    {
        $processExit = new ProcessExit($pid, $status);
        $processExit->setProcessControl($this->processControl);

        return $processExit;
    }

    public function testProcessExit()
    {
        $processExit = $this->getProcessExit(10, 10);

        $this->assertSame(10, $processExit->getPid());
        $this->assertSame(10, $processExit->getStatus());

        $this->assertFalse($processExit->isPropagationStopped());
        $processExit->stopPropagation();
        $this->assertTrue($processExit->isPropagationStopped());
    }

    public function testIsNormalExit()
    {
        $processExit = $this->getProcessExit(10, 10);

        $this->processControl->expects($this->once())
            ->method('ifNormalExit')
            ->with(10)
            ->willReturn(true);

        $this->assertTrue($processExit->isNormalExit());
        $this->assertTrue($processExit->isNormalExit());
    }

    public function testIsExitBecauseNotHandledSignal()
    {
        $processExit = $this->getProcessExit(10, 10);

        $this->processControl->expects($this->once())
            ->method('ifSignalExit')
            ->with(10)
            ->willReturn(true);

        $this->assertTrue($processExit->isExitBecauseNotHandledSignal());
        $this->assertTrue($processExit->isExitBecauseNotHandledSignal());
    }

    public function testExitCode()
    {
        $processExit = $this->getProcessExit(10, 10);

        $this->processControl->expects($this->once())
            ->method('ifNormalExit')
            ->with(10)
            ->willReturn(true);

        $this->processControl->expects($this->once())
            ->method('getExitCode')
            ->with(10)
            ->willReturn(0);

        $this->assertSame(0, $processExit->getExitCode());
        $this->assertSame(0, $processExit->getExitCode());
    }

    public function testExitCodeNonNormalExit()
    {
        $processExit = $this->getProcessExit(10, 10);

        $this->processControl->expects($this->exactly(2))
            ->method('ifNormalExit')
            ->with(10)
            ->willReturn(false);

        $this->assertNull($processExit->getExitCode());
        $this->assertNull($processExit->getExitCode());
    }



    public function testNotHandledSignalNo()
    {
        $processExit = $this->getProcessExit(10, 10);

        $this->processControl->expects($this->once())
            ->method('ifSignalExit')
            ->willReturn(true);

        $this->processControl->expects($this->once())
            ->method('getExitSignalNo')
            ->with(10)
            ->willReturn(0);

        $this->assertSame(0, $processExit->getNotHandledSignalNo());
        $this->assertSame(0, $processExit->getNotHandledSignalNo());
    }

    public function testWithoutNotHandledSignalNo()
    {
        $processExit = $this->getProcessExit(10, 10);

        $this->processControl->expects($this->exactly(2))
            ->method('ifSignalExit')
            ->willReturn(false);

        $this->assertNull($processExit->getNotHandledSignalNo());
        $this->assertNull($processExit->getNotHandledSignalNo());
    }
}
