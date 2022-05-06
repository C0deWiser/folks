<?php

namespace Codewiser\Folks\Controls\Traits;

trait HasMultipleAttribute
{

    protected bool $multiple = false;

    public function multiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;
        return $this;
    }
}
