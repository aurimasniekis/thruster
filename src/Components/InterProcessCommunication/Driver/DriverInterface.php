<?php

namespace Thruster\Components\InterProcessCommunication\Driver;

/**
 * Interface DriverInterface
 *
 * @package Thruster\Components\InterProcessCommunication\Driver
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface DriverInterface
{
    public function initialize();

    public function close();

    public function setSlaveMode();

    public function setMasterMode();

    /**
     * @param mixed $content
     */
    public function write($content);

    /**
     * @return mixed
     */
    public function read();
}
