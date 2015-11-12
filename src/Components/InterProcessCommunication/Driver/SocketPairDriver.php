<?php

namespace Thruster\Components\InterProcessCommunication\Driver;

use Thruster\Components\Socket\SocketPair;
use function Funct\CodeBlocks\false;

/**
 * Class SocketPairDriver
 *
 * @package Thruster\Components\InterProcessCommunication\Driver
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class SocketPairDriver implements DriverInterface
{
    /**
     * @var SocketPair
     */
    protected $socketPair;

    /**
     * @var bool
     */
    protected $slave;

    public function __construct()
    {
        $this->slave = false;
    }

    public function initialize()
    {
        $this->getSocketPair()->initialize();
    }

    public function close()
    {
        $this->getSocketPair()->close();
    }

    public function setSlaveMode()
    {
        $this->getSocketPair()->useRight();

        $this->slave = true;
    }

    public function setMasterMode()
    {
        $this->getSocketPair()->useLeft();
    }

    /**
     * @inheritDoc
     */
    public function write($content)
    {
        if (false($this->getSocketPair()->getSocket()->writeSelect(1))) {
            return;
        }

        $serialized = serialize($content);
        $header     = pack('N', strlen($serialized)); // 4 byte length
        $buffer     = $header . $serialized;
        $total      = strlen($buffer);
        while (true) {
            $sent = $this->getSocketPair()->getSocket()->write($buffer);

            if ($sent >= $total) {
                break;
            }

            $total -= $sent;
            $buffer = substr($buffer, $sent);
        }
    }

    /**
     * @inheritDoc
     */
    public function read()
    {
        if (false($this->getSocketPair()->getSocket()->readSelect(2))) {
            return;
        }

        $header = '';

        do {
            $read = $this->getSocketPair()->getSocket()->read(4 - strlen($header));
            if ('' === $read) {
                return null;
            }

            $header .= $read;
        } while (strlen($header) < 4);

        list($len) = array_values(unpack('N', $header));

        // read the full buffer
        $buffer = '';

        do {
            $read = $this->getSocketPair()->getSocket()->read($len - strlen($buffer));

            if ('' === $read) {
                return null;
            }

            $buffer .= $read;
        } while (strlen($buffer) < $len);

        $data = unserialize($buffer);

        return $data;
    }

    /**
     * @return SocketPair
     */
    public function getSocketPair()
    {
        if ($this->socketPair) {
            return $this->socketPair;
        }

        $this->socketPair = new SocketPair();

        return $this->socketPair;
    }

    /**
     * @param SocketPair $socketPair
     *
     * @return $this
     */
    public function setSocketPair($socketPair)
    {
        $this->socketPair = $socketPair;

        return $this;
    }
}
