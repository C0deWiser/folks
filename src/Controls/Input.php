<?php

namespace Codewiser\Folks\Controls;

use Codewiser\Folks\Controls\Traits\HasAttributeBind;
use Codewiser\Folks\Controls\Traits\HasCast;
use Codewiser\Folks\Controls\Traits\HasLabel;
use Codewiser\Folks\Controls\Traits\HasReadonlyAttribute;
use Codewiser\Folks\Controls\Traits\HasRequiredAttribute;
use Codewiser\Folks\Controls\Traits\HasTypeAttribute;

class Input implements UserControl
{
    use HasAttributeBind,
        HasLabel,
        HasCast,
        HasTypeAttribute,
        HasRequiredAttribute,
        HasReadonlyAttribute;

    public static function make(string $attribute): Input
    {
        return new static($attribute);
    }

    public function toArray()
    {
        return [
            'name' => $this->attribute,
            'label' => $this->getLabel($this->attribute),
            'type' => $this->type,
            'required' => $this->required,
            'readonly' => $this->readonly,
        ];
    }
}
