<?php

namespace Thruster\Components\PosixSignalHandler;

/**
 * Class Signal
 * @package Thruster\Components\PosixSignalHandler
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
class Signal
{
    const MASTER = 0;
    const BROADCAST = -1;

    /**
     * @var int
     */
    protected $signalNo;

    /**
     * @var int
     */
    protected $destination;

    /**
     * @var bool
     */
    protected $propagationStopped;

    public function __construct(int $signalNo, int $destination)
    {
        $this->signalNo = $signalNo;
        $this->destination = $destination;
    }

    /**
     * @return int
     */
    public function getSignalNo(): int
    {
        return $this->signalNo;
    }

    /**
     * @return int
     */
    public function getDestination() : int
    {
        return $this->destination;
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
     * @return Signal
     */
    public function stopPropagation(): Signal
    {
        $this->propagationStopped = true;

        return $this;
    }
}
