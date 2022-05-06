<?php

namespace Codewiser\Folks\Controls\Traits;

trait HasAttributeBind
{
    protected string $attribute;

    public function __construct(string $attribute)
    {
        $this->attribute = $attribute;
    }

    public function __toString()
    {
        return $this->attribute;
    }
}
