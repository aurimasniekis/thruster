<?php

namespace Thruster\Components\InterProcessCommunication\Tests;

use Thruster\Components\InterProcessCommunication\Packet;

class PacketTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Packet
     */
    protected $packet;

    public function setUp()
    {
        $this->packet = new Packet();
    }

    public function testSimple()
    {
        $this->assertSame('packet', $this->packet->getType());
        $this->packet->setContent('foo');
        $this->assertEquals('foo', $this->packet->getContent());

        $connection = $this->getMock('\Thruster\Components\InterProcessCommunication\Connection');
        $this->packet->setConnection($connection);
        $this->assertEquals($connection, $this->packet->getConnection());

        $this->assertFalse($this->packet->isPropagationStopped());
        $this->packet->stopPropagation();
        $this->assertTrue($this->packet->isPropagationStopped());
    }


}
