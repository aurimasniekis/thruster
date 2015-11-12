<?php

namespace Thruster\Wrappers\SocketLib;

use Thruster\Wrappers\SocketLib\SocketLib;

/**
 * Trait SocketLibTrait
 *
 * @package Thruster\Wrappers\SocketLib
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
trait SocketLibTrait
{
    /**
     * @var SocketLib
     */
    protected $socketLib;

    /**
     * @return SocketLib
     */
    public function getSocketLib()
    {
        if ($this->socketLib) {
            return $this->socketLib;
        }

        $this->socketLib = new SocketLib();

        return $this->socketLib;
    }

    /**
     * @param SocketLib $socketLib
     *
     * @return $this
     */
    public function setSocketLib($socketLib)
    {
        $this->socketLib = $socketLib;

        return $this;
    }
}
