<?php

namespace Thruster\Components\SubTask\Packet;

use Thruster\Components\InterProcessCommunication\Packet;

/**
 * Class TaskPacket
 *
 * @package Thruster\Components\SubTask\Packet
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class TaskPacket extends Packet
{
    const NAME = 'thruster_subtask_task';

    /**
     * @inheritDoc
     */
    public function __construct($content)
    {
        parent::__construct(static::NAME, Packet::MASTER, $content);
    }
}
