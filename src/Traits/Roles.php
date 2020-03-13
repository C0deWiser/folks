<?php

/**
 *  Трэйт контроля доступа для модели User на основе ролей
 */

namespace Trunow\Rpac\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use Trunow\Rpac\Role;

/**
 * Trait Roles add Roles to User model
 * @package Trunow\Rpac\Traits
 * @mixin Model
 *
 * @property string $api_token
 * @property Collection|Role[] $roles
 */
trait Roles
{
    /**
     * User belongs to many roles.
     *
     * @return BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Check if User play every given role(s)
     *
     * @param string|array $role
     * @return bool
     */
    public function playRole($role)
    {
        $role = is_array($role) ? $role : explode(' ', $role);
        return count($role) === $this->roles->whereIn('slug', $role)->count();
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function (User $user) {
            if (!$user->api_token) $user->api_token = Str::random(60);
        });
    }

}