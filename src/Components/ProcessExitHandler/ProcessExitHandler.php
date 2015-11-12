<?php

namespace Thruster\Components\ProcessExitHandler;

use Thruster\Components\PosixSignalHandler\SignalSubscriberInterface;
use Thruster\Components\ProcessExitHandler\Exception\ProcessExitHandlerException;
use Thruster\Wrappers\ProcessControl\ProcessControlTrait;

/**
 * Class ProcessExitHandler
 *
 * @package Thruster\Components\ProcessExitHandler
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class ProcessExitHandler implements SignalSubscriberInterface
{
    use ProcessControlTrait;

    /**
     * @var callable[]
     */
    protected $handlers;

    public function __construct()
    {
        $this->handlers = [];
    }

    /**
     * @throws ProcessExitHandlerException
     */
    public function check()
    {
        $pid = $this->getProcessControl()->wait($status, WNOHANG);

        if (0 === $pid) {
            return;
        } elseif (-1 === $pid) {
            if (10 !== $this->getProcessControl()->getLastError()) {
                throw new ProcessExitHandlerException(
                    'pcntl_wait: ' . $this->getProcessControl()->getStringError(
                        $this->getProcessControl()->getLastError()
                    )
                );
            }
        }

        $processExit = new ProcessExit($pid, $status);

        foreach ($this->getHandlers() as $handler) {
            if ($processExit->isPropagationStopped()) {
                break;
            }

            call_user_func(
                $handler,
                $processExit
            );
        }
    }

    /**
     * @param ProcessExitSubscriberInterface $handler
     *
     * @return $this
     */
    public function addHandler(ProcessExitSubscriberInterface $handler) : self
    {
        $this->handlers[] = [$handler, 'onProcessExit'];

        return $this;
    }

    /**
     * @param ProcessExitSubscriberInterface $handler
     *
     * @return $this
     */
    public function removeHandler(ProcessExitSubscriberInterface $handler) : self
    {
        if (false !== ($key = array_search([$handler, 'onProcessExit'], $this->handlers, true))) {
            unset($this->handlers[$key]);
        }

        return $this;
    }

    /**
     * @return callable[]
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedSignals() : array
    {
        return [
            SIGCHLD => ['check']
        ];
    }
}
