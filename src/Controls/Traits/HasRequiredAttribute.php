<?php

namespace Codewiser\Folks\Controls\Traits;

trait HasRequiredAttribute
{

    protected bool $required = false;

    public function required(bool $required = true): self
    {
        $this->required = $required;
        return $this;
    }
}
