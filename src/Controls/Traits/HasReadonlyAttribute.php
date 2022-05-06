<?php

namespace Codewiser\Folks\Controls\Traits;

trait HasReadonlyAttribute
{

    protected bool $readonly = false;

    public function readonly(bool $readonly = true): self
    {
        $this->readonly = $readonly;
        return $this;
    }
}
