<?php

namespace Thruster\Components\InterProcessCommunication;

/**
 * Interface PacketSubscriberInterface
 *
 * @package Thruster\Components\InterProcessCommunication
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface PacketSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedPackets();
}
