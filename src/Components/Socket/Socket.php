<?php

namespace Thruster\Components\Socket;

use function Funct\CodeBlocks\false;
use Thruster\Components\Socket\Exception\SocketException;
use Thruster\Wrappers\SocketLib\SocketLibTrait;

/**
 * Class Socket
 *
 * @package Thruster\Wrappers\Socket
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Socket
{
    use SocketLibTrait;

    /**
     * @var resource
     */
    protected $socket;

    public function close()
    {
        if (is_resource($this->getSocket())) {
            $this->getSocketLib()->close($this->getSocket());

            return true;
        }

        return false;
    }

    public function select(array &$read, array &$write, array &$except, $timeoutSec, $timeoutMicroSec = 0)
    {
        $response = $this->getSocketLib()->select($read, $write, $except, $timeoutSec, $timeoutMicroSec);

        if (false($response)) {
            throw new SocketException(__METHOD__, $this);
        }

        return $response;
    }

    /**
     * @param int $timeoutSec
     * @param int $timeoutMicroSec
     *
     * @return bool
     * @throws SocketException
     */
    public function writeSelect($timeoutSec, $timeoutMicroSec = 0)
    {
        $read = [];
        $write = [$this->getSocket()];
        $execpt = [];

        return (bool) $this->select($read, $write, $execpt, $timeoutSec, $timeoutMicroSec);
    }

    /**
     * @param int $timeoutSec
     * @param int $timeoutMicroSec
     *
     * @return bool
     * @throws SocketException
     */
    public function readSelect($timeoutSec, $timeoutMicroSec = 0)
    {
        $read = [$this->getSocket()];
        $write = [];
        $execpt = [];

        return (bool) $this->select($read, $write, $execpt, $timeoutSec, $timeoutMicroSec);
    }

    /**
     * @param mixed $buffer
     * @param int $length
     *
     * @return int
     * @throws SocketException
     */
    public function write($buffer, int $length = null)
    {
        $response = $this->getSocketLib()->write($this->getSocket(), $buffer, $length);

        if (false($response)) {
            throw new SocketException(__METHOD__, $this);
        }
        
        return $response;
    }

    /**
     * @param int $length
     * @param int $type
     *
     * @return mixed
     * @throws SocketException
     */
    public function read(int $length, int $type = PHP_BINARY_READ)
    {
        $response = $this->getSocketLib()->read($this->getSocket(), $length, $type);
        
        if (false($response)) {
            throw new SocketException(__METHOD__, $this);
        }
        
        return $response;
    }

    /**
     * @return string
     */
    public function getLastErrorMessage()
    {
        $errorCode = $this->getSocketLib()->lastError($this->getSocket());

        return $this->getSocketLib()->stringError($errorCode);
    }

    /**
     * @return resource
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @param resource $socket
     *
     * @return $this
     */
    public function setSocket($socket)
    {
        $this->socket = $socket;

        return $this;
    }
}
