<?php

namespace Codewiser\Folks\Controls\Traits;

trait HasTypeAttribute
{

    protected string $type = 'text';

    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }
}
