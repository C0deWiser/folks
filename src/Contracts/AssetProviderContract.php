<?php

namespace Codewiser\Folks\Contracts;

interface AssetProviderContract
{
    /**
     * Determine if Folks' published assets are up-to-date.
     *
     * @throws \RuntimeException
     */
    public function assetsAreCurrent(): bool;

    /**
     * Get the default JavaScript variables for Folks
     */
    public function scriptVariables(): array;
}
