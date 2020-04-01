<?php

/**
 *  Трэйт контроля доступа для модели User на основе ролей
 */

namespace Codewiser\Rpac\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use Codewiser\Rpac\Role;

/**
 * Trait Roles add Roles to User model
 * @package Codewiser\Rpac\Traits
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
        return $this->belongsToMany(
            Role::class,
            'role_user',
            'user_id',
            'role'
        )->withTimestamps();
    }

    /**
     * Get list of User roles (slugs)
     * @return array|string[]
     */
    public function getRoles()
    {
        return array_merge(['any'], $this->roles->modelKeys());
    }

    /**
     * Check if User plays one of given roles
     *
     * @param string|array $roles
     * @return bool
     */
    public function playRole($roles)
    {
        $roles = is_array($roles) ? $roles : explode(' ', $roles);
        return !!$this->roles()->whereIn('id', $roles)->count();
    }

    /**
     * Check if User plays every given role
     * @param string|array $roles
     * @return bool
     */
    public function playRoles($roles)
    {
        $roles = is_array($roles) ? $roles : explode(' ', $roles);
        return count($roles) === $this->roles()->whereIn('id', $roles)->count();
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function (User $user) {
            /** @var Roles $user */
            if (!$user->api_token) $user->api_token = Str::random(60);
        });
    }

}
