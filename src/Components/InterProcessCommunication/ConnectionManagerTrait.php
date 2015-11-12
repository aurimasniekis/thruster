<?php

namespace Thruster\Components\InterProcessCommunication;

/**
 * Trait ConnectionManagerTrait
 *
 * @package Thruster\Components\InterProcessCommunication
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
trait ConnectionManagerTrait
{
    /**
     * @var ConnectionManager
     */
    protected $connectionManager;

    /**
     * @return ConnectionManager
     */
    public function getConnectionManager()
    {
        return $this->connectionManager;
    }

    /**
     * @param ConnectionManager $connectionManager
     *
     * @return $this
     */
    public function setConnectionManager($connectionManager)
    {
        $this->connectionManager = $connectionManager;

        return $this;
    }
}
