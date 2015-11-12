<?php

namespace Thruster\Components\SubTask;

use Thruster\Components\InterProcessCommunication\Connection;
use Thruster\Components\SubTask\Packet\TaskErrorPacket;
use Thruster\Components\SubTask\Packet\TaskProgressPacket;
use Thruster\Components\SubTask\Packet\TaskResultPacket;

/**
 * Class SubTask
 *
 * @package Thruster\Components\SubTask
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class SubTask
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var callable
     */
    protected $task;

    /**
     * @var callable
     */
    protected $resultCallback;

    /**
     * @var callable
     */
    protected $errorCallback;

    /**
     * @var callable
     */
    protected $progressCallback;

    /**
     * SubTask constructor.
     *
     * @param Connection $connection
     * @param callable   $task
     * @param callable   $resultCallback
     * @param callable   $errorCallback
     * @param callable   $progressCallback
     */
    public function __construct(
        Connection $connection,
        callable $task,
        $resultCallback = null,
        $errorCallback = null,
        $progressCallback = null
    ) {
        $this->connection       = $connection;
        $this->task             = $task;
        $this->resultCallback   = $resultCallback;
        $this->errorCallback    = $errorCallback;
        $this->progressCallback = $progressCallback;
    }

    public function run()
    {
        $progressCallback = function () {
            if ($this->getProgressCallback()) {
                $progressPacket = new TaskProgressPacket(func_get_args());

                $this->getConnection()->sendPacket($progressPacket);
            }
        };

        try {
            $result = call_user_func(
                $this->getTask(),
                $progressCallback
            );
        } catch (\Throwable $t) {
            $e = new WrappedThrowable($t);
            $errorPacket = new TaskErrorPacket($e);

            $this->getConnection()->sendPacket($errorPacket);

            $this->finished();

            return;
        }

        $resultPacket = new TaskResultPacket($result);
        $this->getConnection()->sendPacket($resultPacket);

        $this->finished();
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return callable
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @return callable
     */
    public function getResultCallback()
    {
        return $this->resultCallback;
    }

    /**
     * @return callable
     */
    public function getErrorCallback()
    {
        return $this->errorCallback;
    }

    /**
     * @return callable
     */
    public function getProgressCallback()
    {
        return $this->progressCallback;
    }

    /**
     * @codeCoverageIgnore
     */
    public function finished()
    {
        exit(0);
    }
}
