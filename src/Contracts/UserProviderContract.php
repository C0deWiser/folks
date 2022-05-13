<?php

namespace Codewiser\Folks\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface UserProviderContract
{
    /**
     * Get Model's class name.
     */
    public function className(): string;

    /**
     * Get Builder.
     */
    public function builder(?Authenticatable $user): Builder;

    /**
     * Get User Controls Collection.
     */
    public function schema(): Collection;
}
