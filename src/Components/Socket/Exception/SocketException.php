<?php

namespace Thruster\Components\Socket\Exception;

use Thruster\Components\Socket\Socket;

/**
 * Class SocketException
 *
 * @package Thruster\Components\Socket\Exception
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class SocketException extends \Exception
{
    public function __construct($method, Socket $socket)
    {
        $message = sprintf('%s: %s', $method, $socket->getLastErrorMessage());

        parent::__construct($message);
    }
}
