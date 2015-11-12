<?php

namespace Thruster\Components\SubTask;

use Thruster\Components\InterProcessCommunication\Connection;
use Thruster\Components\InterProcessCommunication\ConnectionManager;
use Thruster\Components\InterProcessCommunication\ConnectionManagerTrait;
use Thruster\Components\InterProcessCommunication\PacketSubscriberInterface;
use Thruster\Components\ProcessExitHandler\ProcessExit;
use Thruster\Components\ProcessExitHandler\ProcessExitSubscriberInterface;
use Thruster\Components\SubTask\Packet\TaskErrorPacket;
use Thruster\Components\SubTask\Packet\TaskProgressPacket;
use Thruster\Components\SubTask\Packet\TaskResultPacket;
use Thruster\Wrappers\ProcessControl\ProcessControlTrait;

/**
 * Class SubTaskManager
 *
 * @package Thruster\Components\SubTask
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class SubTaskManager implements PacketSubscriberInterface, ProcessExitSubscriberInterface
{
    use ConnectionManagerTrait;
    use ProcessControlTrait;

    /**
     * @var SubTask[]
     */
    protected $subTasks;

    /**
     * @var SubTask[]
     */
    protected $subTasksQueue;

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->subTasks      = [];
        $this->subTasksQueue = [];

        $this->connectionManager = $connectionManager;
    }

    public function launchSubTask($task, $resultCallback = null, $errorCallback = null, $progressCallback = null)
    {
        $connectionManager = $this->getConnectionManager();

        $connection = $connectionManager->newConnection();
        $connection->initialize();

        $subTask = $this->getNewSubTask($connection, $task, $resultCallback, $errorCallback, $progressCallback);

        $pid = $this->getProcessControl()->fork();

        if (0 < $pid) {
            $this->subTasks[$pid] = $subTask;
            $connectionManager->addConnection($connection);

            return $pid;
        } else {
            $connection->setSlaveMode();
            $subTask->run();
        }
    }

    public function addSubTask($task, $resultCallback = null, $errorCallback = null, $progressCallback = null)
    {
        $connectionManager = $this->getConnectionManager();

        $connection = $connectionManager->newConnection();
        $connection->initialize();

        $subTask = $this->getNewSubTask($connection, $task, $resultCallback, $errorCallback, $progressCallback);

        $this->subTasksQueue[] = $subTask;
    }

    public function runAll() : array
    {
        $ids               = [];
        $connectionManager = $this->getConnectionManager();

        while ($subTask = array_shift($this->subTasksQueue)) {
            $pid = $this->getProcessControl()->fork();

            if (0 < $pid) {
                $this->subTasks[$pid] = $subTask;
                $connectionManager->addConnection($subTask->getConnection());

                $ids[] = $pid;
            } else {
                $subTask->getConnection()->setSlaveMode();
                $subTask->run();
            }
        }

        return $ids;
    }

    public function waitAll()
    {
        while (count($this->getSubTasks()) > 0) {
            sleep(0.5);
            $this->getProcessControl()->signalDispatch();
        }
    }

    /**
     * @return bool
     */
    public function isAllDone() : bool
    {
        return 0 === count($this->getSubTasks());
    }

    /**
     * @param int $pid
     *
     * @return bool
     */
    public function hasSubTask(int $pid) : bool
    {
        return array_key_exists($pid, $this->subTasks);
    }

    /**
     * @param int $pid
     *
     * @return SubTask
     */
    public function getSubTask(int $pid) : SubTask
    {
        return $this->subTasks[$pid];
    }

    /**
     * @param int $pid
     *
     * @return $this
     */
    public function removeSubTask(int $pid) : self
    {
        if (false === $this->hasSubTask($pid)) {
            return $this;
        }

        unset($this->subTasks[$pid]);

        return $this;
    }

    /**
     * @return SubTask[]
     */
    public function getSubTasksQueue()
    {
        return $this->subTasksQueue;
    }

    /**
     * @return SubTask[]
     */
    public function getSubTasks()
    {
        return $this->subTasks;
    }

    /**
     * @param SubTask[] $subTasks
     */
    public function setSubTasks($subTasks)
    {
        $this->subTasks = $subTasks;
    }

    /**
     * @param Connection $connection
     * @param callable   $task
     * @param callable   $resultCallback
     * @param callable   $errorCallback
     * @param callable   $progressCallback
     *
     * @return SubTask
     */
    protected function getNewSubTask(
        Connection $connection,
        callable $task,
        $resultCallback = null,
        $errorCallback = null,
        $progressCallback = null
    ) : SubTask
    {
        return new SubTask($connection, $task, $resultCallback, $errorCallback, $progressCallback);
    }

    /**
     * @param TaskResultPacket $packet
     */
    public function onResultPacket($packet)
    {
        $subTaskPid = $packet->getFrom();

        if (false === $this->hasSubTask($subTaskPid)) {
            return;
        }

        $subTask = $this->getSubTask($subTaskPid);
        if ($subTask->getResultCallback()) {
            call_user_func(
                $subTask->getResultCallback(),
                $packet->getContent()
            );
        }

        unset($this->subTasks[$subTaskPid]);
    }

    /**
     * @param TaskErrorPacket $packet
     */
    public function onErrorPacket($packet)
    {
        $subTaskPid = $packet->getFrom();

        if (false === $this->hasSubTask($subTaskPid)) {
            return;
        }

        $subTask = $this->getSubTask($subTaskPid);
        if ($subTask->getErrorCallback()) {
            call_user_func(
                $subTask->getErrorCallback(),
                $packet->getContent()
            );
        }

        unset($this->subTasks[$subTaskPid]);
    }

    /**
     * @param TaskProgressPacket $packet
     */
    public function onProgressPacket($packet)
    {
        $subTaskPid = $packet->getFrom();

        if (false === $this->hasSubTask($subTaskPid)) {
            return;
        }

        $subTask = $this->getSubTask($subTaskPid);
        if ($subTask->getProgressCallback()) {
            call_user_func_array(
                $subTask->getProgressCallback(),
                $packet->getContent()
            );
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedPackets() : array
    {
        return [
            TaskResultPacket::NAME   => ['onResultPacket'],
            TaskErrorPacket::NAME    => ['onErrorPacket'],
            TaskProgressPacket::NAME => ['onProgressPacket'],
        ];
    }

    /**
     * @inheritDoc
     */
    public function onProcessExit(ProcessExit $processExit)
    {
        $pid = $processExit->getPid();

        if (false === $this->hasSubTask($pid)) {
            return;
        }

        $this->removeSubTask($pid);
        $processExit->stopPropagation();
    }
}
