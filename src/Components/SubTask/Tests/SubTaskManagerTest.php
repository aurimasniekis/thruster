<?php

namespace Thruster\Components\SubTask\Tests;

use Thruster\Components\ProcessExitHandler\ProcessExit;
use Thruster\Components\SubTask\Packet\TaskErrorPacket;
use Thruster\Components\SubTask\Packet\TaskPacket;
use Thruster\Components\SubTask\Packet\TaskProgressPacket;
use Thruster\Components\SubTask\Packet\TaskResultPacket;
use Thruster\Components\SubTask\SubTaskManager;

class SubTaskManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $processControl;

    /**
     * @var SubTaskManager
     */
    protected $subTaskManager;

    public function setUp()
    {
        $this->connectionManager = $this->getMockBuilder(
            '\Thruster\Components\InterProcessCommunication\ConnectionManager'
        )
            ->getMock();

        $this->processControl = $this->getMockBuilder('\Thruster\Wrappers\ProcessControl\ProcessControl')
            ->getMock();

        $this->subTaskManager = new SubTaskManager($this->connectionManager);
        $this->subTaskManager->setProcessControl($this->processControl);
    }

    public function testLaunchSubTaskMaster()
    {
        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $connection->expects($this->once())
            ->method('initialize');

        $this->connectionManager->expects($this->once())
            ->method('newConnection')
            ->willReturn($connection);

        $this->processControl->expects($this->once())
            ->method('fork')
            ->willReturn(10);

        $this->connectionManager->expects($this->once())
            ->method('addConnection')
            ->with($connection);

        $this->assertSame(10, $this->subTaskManager->launchSubTask(function () {
        }));
        $this->assertTrue($this->subTaskManager->hasSubTask(10));
    }

    public function testLaunchSubTaskSlave()
    {
        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $connection->expects($this->once())
            ->method('initialize');

        $this->connectionManager->expects($this->once())
            ->method('newConnection')
            ->willReturn($connection);

        $this->processControl->expects($this->once())
            ->method('fork')
            ->willReturn(0);

        $connection->expects($this->once())
            ->method('setSlaveMode');

        $subTaskManager = $this->getMockBuilder('\Thruster\Components\SubTask\SubTaskManager')
            ->setConstructorArgs([$this->connectionManager])
            ->setMethods(['getNewSubTask'])
            ->getMock();

        $subTaskManager->setProcessControl($this->processControl);

        $subTask = $this->getMockBuilder('\Thruster\Components\SubTask\SubTask')
            ->disableOriginalConstructor()
            ->getMock();

        $subTaskManager->expects($this->once())
            ->method('getNewSubTask')
            ->willReturn($subTask);

        $subTaskManager->launchSubTask(function () {
        });
    }

    public function testAddSubTask()
    {
        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $connection->expects($this->once())
            ->method('initialize');

        $this->connectionManager->expects($this->once())
            ->method('newConnection')
            ->willReturn($connection);

        $subTaskManager = $this->getMockBuilder('\Thruster\Components\SubTask\SubTaskManager')
            ->setConstructorArgs([$this->connectionManager])
            ->setMethods(['getNewSubTask'])
            ->getMock();

        $subTaskManager->setProcessControl($this->processControl);

        $subTask = $this->getMockBuilder('\Thruster\Components\SubTask\SubTask')
            ->disableOriginalConstructor()
            ->getMock();

        $subTaskManager->expects($this->once())
            ->method('getNewSubTask')
            ->willReturn($subTask);

        $subTaskManager->addSubTask(function () {
        });
        $this->assertCount(1, $subTaskManager->getSubTasksQueue());
        $this->assertEquals($subTask, ($subTaskManager->getSubTasksQueue())[0]);
    }

    public function testRunAllMaster()
    {
        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $connection->expects($this->once())
            ->method('initialize');

        $this->connectionManager->expects($this->once())
            ->method('newConnection')
            ->willReturn($connection);

        $subTaskManager = $this->getMockBuilder('\Thruster\Components\SubTask\SubTaskManager')
            ->setConstructorArgs([$this->connectionManager])
            ->setMethods(['getNewSubTask'])
            ->getMock();

        $subTaskManager->setProcessControl($this->processControl);

        $subTask = $this->getMockBuilder('\Thruster\Components\SubTask\SubTask')
            ->disableOriginalConstructor()
            ->getMock();

        $subTask->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $subTaskManager->expects($this->once())
            ->method('getNewSubTask')
            ->willReturn($subTask);

        $this->processControl->expects($this->once())
            ->method('fork')
            ->willReturn(10);


        $subTaskManager->addSubTask(function () {
        });

        $this->assertEquals([10], $subTaskManager->runAll());

        $this->assertTrue($subTaskManager->hasSubTask(10));

        $this->assertEquals($subTask, $subTaskManager->getSubTask(10));

        $this->assertEquals($subTask, ($subTaskManager->getSubTasks())[10]);

        $subTaskManager->removeSubTask(10);
        $subTaskManager->removeSubTask(10);

        $this->assertFalse($subTaskManager->hasSubTask(10));
    }

    public function testRunAllSlave()
    {
        $connection = $this->getMockBuilder('\Thruster\Components\InterProcessCommunication\Connection')
            ->getMock();

        $connection->expects($this->once())
            ->method('initialize');

        $connection->expects($this->once())
            ->method('setSlaveMode');

        $this->connectionManager->expects($this->once())
            ->method('newConnection')
            ->willReturn($connection);

        $subTaskManager = $this->getMockBuilder('\Thruster\Components\SubTask\SubTaskManager')
            ->setConstructorArgs([$this->connectionManager])
            ->setMethods(['getNewSubTask'])
            ->getMock();

        $subTaskManager->setProcessControl($this->processControl);

        $subTask = $this->getMockBuilder('\Thruster\Components\SubTask\SubTask')
            ->disableOriginalConstructor()
            ->getMock();

        $subTask->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $subTask->expects($this->once())
            ->method('run');

        $subTaskManager->expects($this->once())
            ->method('getNewSubTask')
            ->willReturn($subTask);

        $this->processControl->expects($this->once())
            ->method('fork')
            ->willReturn(0);


        $subTaskManager->addSubTask(function () {
        });
        $subTaskManager->runAll();
    }

    public function testWaitAll()
    {
        $subTaskManager = $this->getMockBuilder('\Thruster\Components\SubTask\SubTaskManager')
            ->setConstructorArgs([$this->connectionManager])
            ->setMethods(['getSubTasks'])
            ->getMock();

        $subTaskManager->setProcessControl($this->processControl);

        $this->processControl->expects($this->once())
            ->method('signalDispatch');

        $subTask = $this->getMockBuilder('\Thruster\Components\SubTask\SubTask')
            ->disableOriginalConstructor()
            ->getMock();

        $subTaskManager->expects($this->at(0))
            ->method('getSubTasks')
            ->willReturn([$subTask]);

        $subTaskManager->expects($this->at(1))
            ->method('getSubTasks')
            ->willReturn([]);

        $subTaskManager->expects($this->at(2))
            ->method('getSubTasks')
            ->willReturn([$subTask]);

        $subTaskManager->expects($this->at(3))
            ->method('getSubTasks')
            ->willReturn([]);

        $subTaskManager->waitAll();

        $this->assertFalse($subTaskManager->isAllDone());
        $this->assertTrue($subTaskManager->isAllDone());
    }

    public function testSetSubTasks()
    {
        $subTask = $this->getMockBuilder('\Thruster\Components\SubTask\SubTask')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subTaskManager->setSubTasks([$subTask]);
        $this->assertEquals([$subTask], $this->subTaskManager->getSubTasks());
    }

    public function testSubscribedMethods()
    {
        $this->assertEquals(
            [
                TaskResultPacket::NAME   => ['onResultPacket'],
                TaskErrorPacket::NAME    => ['onErrorPacket'],
                TaskProgressPacket::NAME => ['onProgressPacket'],
            ],
            $this->subTaskManager->getSubscribedPackets()
        );
    }

    public function testProcessExit()
    {
        $subTask = $this->getMockBuilder('\Thruster\Components\SubTask\SubTask')
            ->disableOriginalConstructor()
            ->getMock();

        $processExit = new ProcessExit(10, 100);

        $this->subTaskManager->setSubTasks([
            10 => $subTask
        ]);

        $this->assertTrue($this->subTaskManager->hasSubTask(10));
        $this->subTaskManager->onProcessExit($processExit);
        $this->assertFalse($this->subTaskManager->hasSubTask(10));

        $this->assertTrue($processExit->isPropagationStopped());
    }

    public function testProcessExitWithoutSubTask()
    {
        $processExit = new ProcessExit(10, 100);

        $this->subTaskManager->onProcessExit($processExit);
    }

    public function testNotExistingSubTaskCallbacks()
    {
        $packet = new TaskPacket('');
        $packet->setFrom(10);

        $this->subTaskManager->onResultPacket($packet);
        $this->subTaskManager->onErrorPacket($packet);
        $this->subTaskManager->onProgressPacket($packet);
    }

    public function testOnResult()
    {
        $subTask = $this->getMockBuilder('\Thruster\Components\SubTask\SubTask')
            ->disableOriginalConstructor()
            ->getMock();

        $subTask->expects($this->exactly(2))
            ->method('getResultCallback')
            ->willReturn(function () {});

        $this->subTaskManager->setSubTasks([10 => $subTask]);

        $packet = new TaskPacket('');
        $packet->setFrom(10);

        $this->subTaskManager->onResultPacket($packet);
    }

    public function testOnError()
    {
        $subTask = $this->getMockBuilder('\Thruster\Components\SubTask\SubTask')
            ->disableOriginalConstructor()
            ->getMock();

        $subTask->expects($this->exactly(2))
            ->method('getErrorCallback')
            ->willReturn(function () {});

        $this->subTaskManager->setSubTasks([10 => $subTask]);

        $packet = new TaskPacket('');
        $packet->setFrom(10);

        $this->subTaskManager->onErrorPacket($packet);
    }

    public function testOnProgress()
    {
        $subTask = $this->getMockBuilder('\Thruster\Components\SubTask\SubTask')
            ->disableOriginalConstructor()
            ->getMock();

        $subTask->expects($this->exactly(2))
            ->method('getProgressCallback')
            ->willReturn(function () {});

        $this->subTaskManager->setSubTasks([10 => $subTask]);

        $packet = new TaskPacket([]);
        $packet->setFrom(10);

        $this->subTaskManager->onProgressPacket($packet);
    }
}
