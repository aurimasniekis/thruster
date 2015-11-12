<?php

namespace Thruster\Wrappers\Posix;

/**
 * Trait PosixTrait
 * @package Thruster\Wrappers\Posix
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
trait PosixTrait
{
    /**
     * @var Posix
     */
    protected $posix;

    /**
     * @return Posix
     */
    public function getPosix() : Posix
    {
        if ($this->posix) {
            return $this->posix;
        }

        $this->posix = new Posix();

        return $this->posix;
    }

    /**
     * @param Posix $posix
     *
     * @return $this
     */
    public function setPosix(Posix $posix)
    {
        $this->posix = $posix;

        return $this;
    }
}
