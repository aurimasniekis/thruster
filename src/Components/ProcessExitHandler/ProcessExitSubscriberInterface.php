<?php

namespace Thruster\Components\ProcessExitHandler;

/**
 * Interface ProcessExitSubscriberInterface
 *
 * @package Thruster\Components\ProcessExitHandler
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface ProcessExitSubscriberInterface
{
    /**
     * @param ProcessExit $processExit
     */
    public function onProcessExit(ProcessExit $processExit);
}
