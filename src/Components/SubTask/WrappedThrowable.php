<?php

namespace Thruster\Components\SubTask;

/**
 * Class WrappedThrowable
 *
 * @package Thruster\Components\SubTask
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class WrappedThrowable extends \Exception
{
    /**
     * @inheritDoc
     */
    public function __construct(\Throwable $t)
    {
        $this->message = $t->getMessage();
        $this->code = $t->getCode();
        $this->file = $t->getFile();
        $this->line = $t->getLine();
    }
}
