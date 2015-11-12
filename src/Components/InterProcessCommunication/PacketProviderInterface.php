<?php

namespace Thruster\Components\InterProcessCommunication;

/**
 * Interface PacketProviderInterface
 *
 * @package Thruster\Components\InterProcessCommunication
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface PacketProviderInterface
{
    /**
     * @param callable $callback
     */
    public function onPacketReceived($callback);
}
