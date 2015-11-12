<?php

namespace Thruster\Components\SubTask\Tests;

use Thruster\Components\SubTask\SubTask;

class SubTaskTest extends \PHPUnit_Framework_TestCase
{

    public function testRun()
    {
        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $callback = function () {
        };

        $subTask = $this->getMockBuilder('\Thruster\Components\SubTask\SubTask')
            ->setConstructorArgs([$connection, $callback])
            ->setMethods(['finished'])
            ->getMock();

        $subTask->expects($this->once())
            ->method('finished');

        $subTask->run();
    }

    public function testRunError()
    {
        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $callbackException = function () {
            throw new \Exception();
        };

        $subTask = $this->getMockBuilder('\Thruster\Components\SubTask\SubTask')
            ->setConstructorArgs([$connection, $callbackException])
            ->setMethods(['finished'])
            ->getMock();

        $subTask->expects($this->once())
            ->method('finished');

        $subTask->run();
    }
    public function testCallBacks()
    {
        $callback = function () {
        };

        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $subTask = new SubTask($connection, $callback, $callback, $callback, $callback);

        $this->assertEquals($callback, $subTask->getResultCallback());
        $this->assertEquals($callback, $subTask->getErrorCallback());
        $this->assertEquals($callback, $subTask->getProgressCallback());
    }
}
