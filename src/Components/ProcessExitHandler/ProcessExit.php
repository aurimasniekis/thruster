<?php

namespace Thruster\Components\ProcessExitHandler;

use Thruster\Wrappers\ProcessControl\ProcessControlTrait;
use function Funct\CodeBlocks\not_null;

/**
 * Class ProcessExit
 *
 * @package Thruster\Components\ProcessExitHandler
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class ProcessExit
{
    use ProcessControlTrait;

    /**
     * @var int
     */
    protected $pid;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var bool
     */
    protected $normalExit;

    /**
     * @var bool
     */
    protected $notHandledSignalExit;

    /**
     * @var int
     */
    protected $notHandledSignalNo;

    /**
     * @var int
     */
    protected $exitCode;

    /**
     * @var bool
     */
    protected $propagationStopped;

    public function __construct(int $pid, int $status)
    {
        $this->pid = $pid;
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function isNormalExit(): bool
    {
        if ($this->normalExit) {
            return $this->normalExit;
        }

        $this->normalExit = $this->getProcessControl()->ifNormalExit($this->getStatus());

        return $this->normalExit;
    }

    /**
     * @return bool
     */
    public function isExitBecauseNotHandledSignal(): bool
    {
        if ($this->notHandledSignalExit) {
            return $this->notHandledSignalExit;
        }

        $this->notHandledSignalExit = $this->getProcessControl()->ifSignalExit($this->getStatus());

        return $this->notHandledSignalExit;
    }

    /**
     * @return int|null
     */
    public function getExitCode()
    {
        if (not_null($this->exitCode)) {
            return $this->exitCode;
        }

        if (false === $this->isNormalExit()) {
            return;
        }

        $this->exitCode = $this->getProcessControl()->getExitCode($this->getStatus());

        return $this->exitCode;
    }

    /**
     * @return int|null
     */
    public function getNotHandledSignalNo()
    {
        if (not_null($this->notHandledSignalNo)) {
            return $this->notHandledSignalNo;
        }

        if (false === $this->isExitBecauseNotHandledSignal()) {
            return;
        }

        $this->notHandledSignalNo = $this->getProcessControl()->getExitSignalNo($this->getStatus());

        return $this->notHandledSignalNo;
    }

    /**
     * @return int
     */
    public function getPid() : int
    {
        return $this->pid;
    }

    /**
     * @return int
     */
    public function getStatus() : int
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        if (null === $this->propagationStopped) {
            $this->propagationStopped = false;
        }

        return $this->propagationStopped;
    }

    /**
     * @return $this
     */
    public function stopPropagation() : self
    {
        $this->propagationStopped = true;

        return $this;
    }
}
