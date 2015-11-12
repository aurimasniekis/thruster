<?php

namespace Thruster\Components\PosixSignalHandler\Tests;

use Thruster\Components\PosixSignalHandler\Signal;

class SignalTest extends \PHPUnit_Framework_TestCase
{

    public function testSignal()
    {
        $signalNo = 9;
        $destination = 1;

        $signal = new Signal($signalNo, $destination);

        $this->assertSame($signalNo, $signal->getSignalNo());
        $this->assertSame($destination, $signal->getDestination());
    }

    public function testSignalWithStoppedPropogation()
    {
        $signal = new Signal(9, 1);

        $this->assertFalse($signal->isPropagationStopped());

        $signal->stopPropagation();

        $this->assertTrue($signal->isPropagationStopped());
    }
}
