<?php

namespace Codewiser\Folks\Contracts;

/**
 * Role model or role enum should conform this contract.
 *
 * @property-read string $name
 */
interface RoleContract
{
    public function caption(): string;

    public function description(): ?string;
}
