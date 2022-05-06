<?php

namespace Codewiser\Folks\Contracts;

/**
 * Role Model/Enum conforms this contract.
 *
 * @property-read string $name
 */
interface RoleContract
{
    public function caption(): string;

    public function description(): ?string;
}
