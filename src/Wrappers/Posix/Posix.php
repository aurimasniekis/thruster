<?php

namespace Thruster\Wrappers\Posix;

/**
 * Class Posix
 * @package Thruster\Wrappers\Posix
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
class Posix
{
    /**
     * Return the current process identifier
     *
     * @return int Returns the identifier, as an integer.
     */
    public function getPid(): int
    {
        return posix_getpid();
    }

    /**
     * Return the parent process identifier
     *
     * @return int Returns the identifier, as an integer.
     */
    public function getParentPid(): int
    {
        return posix_getppid();
    }

    /**
     * Send a signal to a process
     *
     * @param int $pid      The process identifier.
     * @param int $signalNo One of the PCNTL signals constants.
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function kill(int $pid, int $signalNo): bool
    {
        return posix_kill($pid, $signalNo);
    }

    /**
     * Make the current process a session leader
     *
     * @return int
     */
    public function setSid()
    {
        return posix_setsid();
    }
}
