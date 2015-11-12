<?php

namespace Thruster\Components\PosixSignalHandler;

/**
 * Interface SignalSubscriberInterface
 * @package Thruster\Components\PosixSignalHandler
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
interface SignalSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedSignals() : array;
}
