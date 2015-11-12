<?php

namespace Thruster\Components\InterProcessCommunication;

/**
 * Class Packet
 *
 * @package Thruster\Components\InterProcessCommunication
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Packet
{
    const MASTER = 0;
    const BROADCAST = -1;

    /**
     * @var int
     */
    protected $from;

    /**
     * @var int
     */
    protected $destination;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var mixed
     */
    protected $content;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $propagationStopped;

    /**
     * @param string $type
     * @param int    $destination
     * @param mixed  $content
     * @param int    $from
     */
    public function __construct(string $type = 'packet', int $destination = null, $content = null, int $from = null)
    {
        $this->destination = $destination;
        $this->type        = $type;
        $this->content     = $content;
    }

    /**
     * @return int
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @return int
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param int $from
     *
     * @return $this
     */
    public function setFrom($from) : self
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     *
     * @return $this
     */
    public function setContent($content) : self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param Connection $connection
     *
     * @return $this
     */
    public function setConnection($connection) : self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        if (null === $this->propagationStopped) {
            $this->propagationStopped = false;
        }

        return $this->propagationStopped;
    }

    /**
     * @return $this
     */
    public function stopPropagation() : self
    {
        $this->propagationStopped = true;

        return $this;
    }
}
