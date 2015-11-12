<?php

namespace Thruster\Components\ProcessExitHandler\Tests;

use Thruster\Components\ProcessExitHandler\ProcessExitHandlerTrait;

class ProcessExitHandlerTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testTrait()
    {
        $processExitHandler = $this->getMockBuilder(
            '\Thruster\Components\ProcessExitHandler\ProcessExitHandler'
        )
            ->getMock();

        $class = new class {
            use ProcessExitHandlerTrait;
        };

        $class->setProcessExitHandler($processExitHandler);
        $this->assertEquals($processExitHandler, $class->getProcessExitHandler());
    }
}
