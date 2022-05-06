<?php

namespace Codewiser\Folks\Controls\Traits;

trait HasCast
{

    protected ?string $cast = null;

    public function __invoke($value)
    {
        switch ($this->cast) {
            case 'boolean':
                return (boolean)$value;
            case 'number':
                return $value * 1;
            default:
                return $value;
        }
    }

    public function cast($cast): self
    {
        $this->cast = $cast;
        return $this;
    }
}
