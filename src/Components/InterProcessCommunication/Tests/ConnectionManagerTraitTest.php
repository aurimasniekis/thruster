<?php

namespace Thruster\Components\InterProcessCommunication\Tests;

use Thruster\Components\InterProcessCommunication\ConnectionManagerTrait;

class ConnectionManagerTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testTrait()
    {
        $connectionManager = $this->getMockBuilder(
            '\Thruster\Components\InterProcessCommunication\ConnectionManager'
        )
            ->getMock();

        $class = new class {
            use ConnectionManagerTrait;
        };

        $class->setConnectionManager($connectionManager);
        $this->assertEquals($connectionManager, $class->getConnectionManager());
    }
}
