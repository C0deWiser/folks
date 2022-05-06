<?php

namespace Codewiser\Folks\Controls;


use Codewiser\Folks\Controls\Traits\HasAttributeBind;
use Codewiser\Folks\Controls\Traits\HasCast;
use Codewiser\Folks\Controls\Traits\HasLabel;

class Label implements UserControl
{
    use HasAttributeBind,
        HasLabel,
        HasCast;

    public static function make(string $attribute): Label
    {
        return new static($attribute);
    }

    public function toArray()
    {
        return [
            'name' => $this->attribute,
            'label' => $this->getLabel($this->attribute),
        ];
    }
}
