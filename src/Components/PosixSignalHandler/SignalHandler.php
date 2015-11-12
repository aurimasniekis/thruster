<?php

namespace Thruster\Components\PosixSignalHandler;

use Thruster\Wrappers\Posix\PosixTrait;
use Thruster\Wrappers\ProcessControl\ProcessControlTrait;

/**
 * Class SignalHandler
 *
 * @package Thruster\Components\PosixSignalHandler
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class SignalHandler
{
    use ProcessControlTrait;
    use PosixTrait;

    /**
     * @var array
     */
    protected $signalHandlers;

    /**
     * @var array
     */
    protected $sortedSignalHandlers;

    public function __construct()
    {
        $this->signalHandlers       = [];
        $this->sortedSignalHandlers = [];
    }

    /**
     * @param Signal $signal
     *
     * @return $this
     */
    public function send(Signal $signal) : self
    {
        $destination = $signal->getDestination();

        $this->getPosix()->kill($destination, $signal->getSignalNo());

        return $this;
    }

    /**
     * @return $this
     */
    public function clearHandlers() : self
    {
        foreach ($this->signalHandlers as $signalNo => $handlers) {
            $this->getProcessControl()->signal($signalNo, SIG_DFL);
        }

        $this->signalHandlers       = [];
        $this->sortedSignalHandlers = [];

        return $this;
    }

    /**
     * @param int      $signalNo
     * @param callable $handler
     * @param int      $priority
     *
     * @return $this
     */
    public function addHandler(int $signalNo, callable $handler, int $priority = 0) : self
    {
        $this->signalHandlers[$signalNo][$priority][] = $handler;
        unset($this->sortedSignalHandlers[$signalNo]);

        $this->getProcessControl()->signal($signalNo, [$this, 'handleSignal']);

        return $this;
    }

    /**
     * @param int $signalNo
     *
     * @return SignalHandler
     */
    public function handleSignal(int $signalNo) : self
    {
        $signal = new Signal($signalNo, -1);

        $handlers = $this->getHandlers($signalNo);

        foreach ($handlers as $handler) {
            call_user_func($handler, $signal, $this);
            if ($signal->isPropagationStopped()) {
                break;
            }
        }

        return $this;
    }

    /**
     * @param int      $signalNo
     * @param callable $handler
     *
     * @return $this
     */
    public function removeHandler(int $signalNo, callable $handler) : self
    {
        if (!isset($this->signalHandlers[$signalNo])) {
            return $this;
        }

        foreach ($this->signalHandlers[$signalNo] as $priority => $handlers) {
            if (false !== ($key = array_search($handler, $handlers, true))) {
                unset($this->signalHandlers[$signalNo][$priority][$key], $this->sortedSignalHandlers[$signalNo]);
            }

            if (count($this->signalHandlers[$signalNo][$priority]) < 1) {
                unset($this->signalHandlers[$signalNo][$priority]);
            }
        }

        if (count($this->signalHandlers[$signalNo]) < 1) {
            $this->getProcessControl()->signal($signalNo, SIG_DFL);
            unset($this->signalHandlers[$signalNo]);
        }

        return $this;
    }

    /**
     * @param int $signalNo
     *
     * @return bool
     */
    public function hasHandlers(int $signalNo = null) : bool
    {
        return (bool)count($this->getHandlers($signalNo));
    }

    /**
     * @param int  $signalNo
     *
     * @return array
     */
    public function getHandlers(int $signalNo = null) : array
    {
        if (null !== $signalNo) {
            if (!isset($this->sortedSignalHandlers[$signalNo])) {
                $this->sortHandlers($signalNo);
            }

            return $this->sortedSignalHandlers[$signalNo];
        }

        foreach ($this->signalHandlers as $signal => $signalHandlers) {
            if (!isset($this->sortedSignalHandlers[$signal])) {
                $this->sortHandlers($signal);
            }
        }

        return array_filter($this->sortedSignalHandlers);
    }

    /**
     * @param SignalSubscriberInterface $subscriber
     *
     * @return $this
     */
    public function addSubscriber(SignalSubscriberInterface $subscriber) : self
    {
        foreach ($subscriber->getSubscribedSignals() as $signalNo => $params) {
            if (is_string($params)) {
                $this->addHandler($signalNo, [$subscriber, $params]);
            } elseif (is_string($params[0])) {
                $this->addHandler($signalNo, [$subscriber, $params[0]], $params[1] ?? 0);
            } else {
                foreach ($params as $listener) {
                    $this->addHandler($signalNo, [$subscriber, $listener[0]], $listener[1] ?? 0);
                }
            }
        }

        return $this;
    }

    /**
     * @param SignalSubscriberInterface $subscriber
     *
     * @return $this
     */
    public function removeSubscriber(SignalSubscriberInterface $subscriber) : self
    {
        foreach ($subscriber->getSubscribedSignals() as $signalNo => $params) {
            if (is_array($params) && is_array($params[0])) {
                foreach ($params as $listener) {
                    $this->removeHandler($signalNo, [$subscriber, $listener[0]]);
                }
            } else {
                $this->removeHandler($signalNo, [$subscriber, is_string($params) ? $params : $params[0]]);
            }
        }

        return $this;
    }

    /**
     * Sorts the internal list of handlers for the given signal by priority.
     *
     * @param int $signalNo The no of the signal.
     *
     * @return $this
     */
    protected function sortHandlers(int $signalNo) : self
    {
        $this->sortedSignalHandlers[$signalNo] = [];

        if (isset($this->signalHandlers[$signalNo])) {
            krsort($this->signalHandlers[$signalNo]);
            $this->sortedSignalHandlers[$signalNo] = call_user_func_array('array_merge',
                $this->signalHandlers[$signalNo]);
        }

        return $this;
    }
}
