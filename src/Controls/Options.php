<?php

namespace Codewiser\Folks\Controls;

use Codewiser\Folks\Controls\Traits\HasAttributeBind;
use Codewiser\Folks\Controls\Traits\HasCast;
use Codewiser\Folks\Controls\Traits\HasLabel;
use Codewiser\Folks\Controls\Traits\HasMultipleAttribute;
use Codewiser\Folks\Controls\Traits\HasOptionList;
use Codewiser\Folks\Controls\Traits\HasReadonlyAttribute;
use Codewiser\Folks\Controls\Traits\HasRequiredAttribute;

class Options implements UserControl
{
    use HasAttributeBind,
        HasLabel,
        HasCast,
        HasRequiredAttribute,
        HasReadonlyAttribute,
        HasMultipleAttribute,
        HasOptionList;

    public static function make(string $attribute): Options
    {
        return new static($attribute);
    }

    public function toArray()
    {
        return [
                'name' => $this->attribute,
                'label' => $this->getLabel($this->attribute),
                'required' => $this->required,
                'readonly' => $this->readonly,
                'multiple' => $this->multiple,
                'options' => collect($this->options)->toArray(),
            ];
    }
}
