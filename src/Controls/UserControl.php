<?php

namespace Codewiser\Folks\Controls;

use Illuminate\Contracts\Support\Arrayable;
use Stringable;

interface UserControl extends Arrayable, Stringable
{
    /**
     * Label value for the user control.
     *
     * @return string
     */
    public function __toString();

    /**
     * Cast given value with user control rules.
     *
     * @param $value
     * @return mixed
     */
    public function __invoke($value);

    /**
     * Set control cast rules: string, number, boolean etc.
     *
     * @param $cast
     * @return self
     */
    public function cast($cast): self;

    /**
     * Set label for the control.
     *
     * @param string $label
     * @return self
     */
    public function label(string $label): self;
}
