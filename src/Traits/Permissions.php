<?php


namespace Codewiser\Rpac\Traits;


use Codewiser\Rpac\Policies\RpacPolicy;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;

/**
 * Get list of authorized actions (uses Laravel Policies)
 * @package Codewiser\Rpac\Traits
 */
trait Permissions
{
    public static function authorizedActions(?User $user)
    {
        if (($policy = Gate::getPolicyFor(static::class)) && $policy instanceof RpacPolicy) {
            return $policy->getAbilities($user);
        } else {
            return [];
        }
    }
    /**
     * Get list of actions allowed to given User against current Model
     * @param User|null $user
     * @return array|string[]
     */
    public function getAuthorizedActions(?User $user)
    {
        if (($policy = Gate::getPolicyFor($this)) && $policy instanceof RpacPolicy) {
            return $policy->getAbilities($user, $this);
        } else {
            return [];
        }
    }
}