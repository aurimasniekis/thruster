<?php

namespace Thruster\Wrappers\ProcessControl;

/**
 * Trait ProcessControlTrait
 * @package Thruster\Wrappers\ProcessControl
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
trait ProcessControlTrait
{
    /**
     * @var ProcessControl
     */
    protected $processControl;

    /**
     * @return ProcessControl
     */
    public function getProcessControl() : ProcessControl
    {
        if ($this->processControl) {
            return $this->processControl;
        }

        $this->processControl = new ProcessControl();

        return $this->processControl;
    }

    /**
     * @param ProcessControl $processControl
     *
     * @return $this
     */
    public function setProcessControl(ProcessControl $processControl)
    {
        $this->processControl = $processControl;

        return $this;
    }
}
