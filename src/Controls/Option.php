<?php

namespace Codewiser\Folks\Controls;

use Codewiser\Folks\Controls\Traits\HasAttributeBind;
use Codewiser\Folks\Controls\Traits\HasLabel;
use Illuminate\Contracts\Support\Arrayable;

class Option implements Arrayable
{
    use HasAttributeBind,
        HasLabel;

    public static function make(string $value): self
    {
        return new static($value);
    }

    public function toArray()
    {
        return [
            'value' => $this->attribute,
            'caption' => $this->getLabel($this->attribute)
        ];
    }
}
