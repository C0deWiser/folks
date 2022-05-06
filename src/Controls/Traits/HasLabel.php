<?php

namespace Codewiser\Folks\Controls\Traits;

use Illuminate\Support\Str;

trait HasLabel
{
    protected ?string $label = null;

    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    protected function getLabel(string $fallback): string
    {
        return $this->label ?? Str::replace('_', ' ', Str::title($fallback));
    }
}
