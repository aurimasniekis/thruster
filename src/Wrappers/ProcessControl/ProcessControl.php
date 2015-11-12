<?php

namespace Thruster\Wrappers\ProcessControl;

/**
 * Class ProcessControl
 * @package Thruster\Wrappers\ProcessControl
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
class ProcessControl
{
    /**
     * Forks the currently running process
     *
     * @return int
     */
    public function fork(): int
    {
        return pcntl_fork();
    }

    /**
     * Installs a signal handler
     *
     * @param int $sigNo The signal number.
     * @param callable|int $handler The signal handler. This may be either a callable, which will be invoked to
     *                                      handle the signal, or either of the two global constants SIG_IGN or SIG_DFL,
     *                                      which will ignore the signal or restore the default signal handler
     *                                      respectively.
     * @param bool $restartSyscalls Specifies whether system call restarting should be used when this signal
     *                                      arrives.
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function signal(int $sigNo, $handler, bool $restartSyscalls = true): bool
    {
        return pcntl_signal($sigNo, $handler, $restartSyscalls);
    }

    /**
     * Calls signal handlers for pending signals
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function signalDispatch(): bool
    {
        return pcntl_signal_dispatch();
    }

    /**
     * Waits on or returns the status of a forked child
     *
     * @param int $status
     * @param int $options
     *
     * @return int returns the process ID of the child which exited, -1 on error or zero if WNOHANG was provided as an
     * option (on wait3-available systems) and no child was available.
     */
    public function wait(&$status, int $options = 0): int
    {
        return pcntl_wait($status, $options);
    }

    /**
     * Checks if status code represents a normal exit
     *
     * @param int $status The status parameter is the status parameter supplied to a successful call to pcntl_waitpid().
     *
     * @return bool TRUE if the child status code represents a normal exit, FALSE otherwise.
     */
    public function ifNormalExit(int $status): bool
    {
        return pcntl_wifexited($status);
    }

    /**
     * Checks whether the status code represents a termination due to a signal
     *
     * @param int $status The status parameter is the status parameter supplied to a successful call to pcntl_waitpid().
     *
     * @return bool TRUE if the child process exited because of a signal which was not caught, FALSE otherwise.
     */
    public function ifSignalExit(int $status): bool
    {
        return pcntl_wifsignaled($status);
    }

    /**
     * Returns the return code of a terminated child
     *
     * @param int $status The status parameter is the status parameter supplied to a successful call to pcntl_waitpid().
     *
     * @return int Returns the return code, as an integer.
     */
    public function getExitCode(int $status): int
    {
        return pcntl_wexitstatus($status);
    }

    /**
     * Returns the signal which caused the child to terminate
     *
     * @param int $status The status parameter is the status parameter supplied to a successful call to pcntl_waitpid().
     *
     * @return int Returns the signal number, as an integer.
     */
    public function getExitSignalNo(int $status): int
    {
        return pcntl_wtermsig($status);
    }

    /**
     * Retrieve the error number set by the last pcntl function which failed
     *
     * @return int
     */
    public function getLastError()
    {
        return pcntl_get_last_error();
    }

    /**
     * Retrieve the system error message associated with the given errorNo
     *
     * @param int $errorCode
     *
     * @return string
     */
    public function getStringError(int $errorCode)
    {
        return pcntl_strerror($errorCode);
    }
}
