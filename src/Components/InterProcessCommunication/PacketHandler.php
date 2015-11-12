<?php

namespace Thruster\Components\InterProcessCommunication;

/**
 * Class PacketHandler
 *
 * @package Thruster\Components\InterProcessCommunication
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class PacketHandler
{
    /**
     * @var array
     */
    protected $handlers;

    /**
     * @var array
     */
    protected $sortedHandlers;

    public function __construct()
    {
        $this->handlers = [];
        $this->sortedHandlers = [];
    }

    /**
     * @param PacketProviderInterface $provider
     */
    public function addProvider(PacketProviderInterface $provider)
    {
        $provider->onPacketReceived([$this, 'receivedPackage']);
    }

    /**
     * @param Packet $packet
     * @param Connection $connection
     */
    public function receivedPackage($packet, $connection)
    {
        $packet->setConnection($connection);

        $this->dispatch($packet);
    }

    /**
     * @param Packet $packet
     *
     * @return Packet
     */
    public function dispatch(Packet $packet): Packet
    {
        $packetType = $packet->getType();

        if (!isset($this->handlers[$packetType])) {
            return $packet;
        }

        $this->doDispatch($this->getHandlers($packetType), $packet);

        return $packet;
    }

    /**
     * @param null $packetType
     * @param bool $withPriorities
     *
     * @return array
     */
    public function getHandlers($packetType = null, $withPriorities = false): array
    {
        if (true === $withPriorities) {
            return $packetType ? $this->handlers[$packetType] : array_filter($this->handlers);
        }

        if (null !== $packetType) {
            if (!isset($this->sortedHandlers[$packetType])) {
                $this->sortHandlers($packetType);
            }

            return $this->sortedHandlers[$packetType];
        }


        foreach ($this->handlers as $packetType => $packetHandlers) {
            if (!isset($this->sortedHandlers[$packetType])) {
                $this->sortHandlers($packetType);
            }
        }

        return array_filter($this->sortedHandlers);
    }

    /**
     * @param null $packetType
     *
     * @return bool
     */
    public function hasHandlers($packetType = null): bool
    {
        return (bool)count($this->getHandlers($packetType));
    }

    /**
     * @param string $packetType
     * @param        $handler
     * @param int    $priority
     *
     * @return $this
     */
    public function addHandler(string $packetType, $handler, $priority = 0)
    {
        $this->handlers[$packetType][$priority][] = $handler;

        unset($this->sortedHandlers[$packetType]);

        return $this;
    }

    /**
     * @param $packetType
     * @param $handler
     *
     * @return $this
     */
    public function removeHandler($packetType, $handler)
    {
        if (!isset($this->handlers[$packetType])) {
            return $this;
        }

        foreach ($this->handlers[$packetType] as $priority => $handlers) {
            if (false !== ($key = array_search($handler, $handlers, true))) {
                unset($this->handlers[$packetType][$priority][$key], $this->sortedHandlers[$packetType]);
            }

            if (count($this->handlers[$packetType][$priority]) < 1) {
                unset($this->handlers[$packetType][$priority]);
            }
        }

        if (count($this->handlers[$packetType]) < 1) {
            unset($this->handlers[$packetType]);
        }

        return $this;
    }

    /**
     * @param PacketSubscriberInterface $subscriber
     *
     * @return $this
     */
    public function addSubscriber(PacketSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedPackets() as $packetType => $params) {
            if (is_string($params)) {
                $this->addHandler($packetType, [$subscriber, $params]);
            } elseif (is_string($params[0])) {
                $this->addHandler($packetType, [$subscriber, $params[0]], $params[1] ?? 0);
            } else {
                foreach ($params as $handler) {
                    $this->addHandler($packetType, [$subscriber, $handler[0]], $handler[1] ?? 0);
                }
            }
        }

        return $this;
    }

    /**
     * @param PacketSubscriberInterface $subscriber
     *
     * @return $this
     */
    public function removeSubscriber(PacketSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedPackets() as $packetType => $params) {
            if (is_array($params) && is_array($params[0])) {
                foreach ($params as $handler) {
                    $this->removeHandler($packetType, [$subscriber, $handler[0]]);
                }
            } else {
                $this->removeHandler($packetType, [$subscriber, is_string($params) ? $params : $params[0]]);
            }
        }

        return $this;
    }


    /**
     * @param array  $handlers
     * @param Packet $packet
     */
    protected function doDispatch($handlers, Packet $packet)
    {
        foreach ($handlers as $handler) {
            call_user_func($handler, $packet, $this);
            if ($packet->isPropagationStopped()) {
                break;
            }
        }
    }

    /**
     * @param string $packetType
     */
    protected function sortHandlers(string $packetType)
    {
        $this->sortedHandlers[$packetType] = [];

        if (isset($this->handlers[$packetType])) {
            krsort($this->handlers[$packetType]);

            $this->sortedHandlers[$packetType] = call_user_func_array('array_merge', $this->handlers[$packetType]);
        }
    }
}
