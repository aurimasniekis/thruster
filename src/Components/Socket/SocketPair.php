<?php

namespace Thruster\Components\Socket;

use function Funct\CodeBlocks\false;
use Thruster\Components\Socket\Exception\SocketException;
use Thruster\Components\Socket\Exception\SocketPairException;
use Thruster\Wrappers\SocketLib\SocketLibTrait;

/**
 * Class SocketPair
 *
 * @package Thruster\Components\Socket
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class SocketPair
{
    use SocketLibTrait;

    /**
     * @var int
     */
    protected $domain;

    /**
     * @var int
     */
    protected $type;

    /**
     * @var int
     */
    protected $protocol;

    /**
     * @var Socket
     */
    protected $left;

    /**
     * @var Socket
     */
    protected $right;

    /**
     * @var Socket
     */
    protected $socket;

    public function __construct(int $domain = AF_UNIX, int $type = SOCK_STREAM, int $protocol = 0)
    {
        $this->domain = $domain;
        $this->type = $type;
        $this->protocol = $protocol;
    }

    public function initialize()
    {
        $sockets = [];

        $response = $this->getSocketLib()->createPair(
            $this->getDomain(),
            $this->getType(),
            $this->getProtocol(),
            $sockets
        );

        if (false($response)) {
            throw new SocketPairException($this->getSocketLib()->stringError($this->getSocketLib()->lastError()));
        }

        $this->left = new Socket();
        $this->left->setSocket(reset($sockets));

        $this->right = new Socket();
        $this->right->setSocket(end($sockets));
    }

    public function close()
    {
        $left = $this->getLeft();
        $right = $this->getRight();

        if ($left) {
            $left->close();
        }

        if ($right) {
            $right->close();
        }
    }

    public function useLeft()
    {
        $this->getRight()->close();
        $this->right = null;

        $this->socket = $this->getLeft();
    }

    public function useRight()
    {
        $this->getLeft()->close();
        $this->left = null;

        $this->socket = $this->getRight();
    }

    /**
     * @return Socket
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @return Socket
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * @param Socket $right
     *
     * @return $this
     */
    public function setRight($right)
    {
        $this->right = $right;

        return $this;
    }

    /**
     * @return Socket
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param Socket $left
     *
     * @return $this
     */
    public function setLeft($left)
    {
        $this->left = $left;

        return $this;
    }

    /**
     * @return int
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getProtocol()
    {
        return $this->protocol;
    }
}
