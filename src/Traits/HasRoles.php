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
trait HasRoles
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
     * Get list of User roles (slugs)
     * @return array|string[]
     */
    public function getRoles()
    {
        return array_merge(['any'], $this->roles->modelKeys());
    }

    /**
     * Check if User plays one of given roles (array or space separated string)
     *
     * @param string|array $roles
     * @return bool
     */
    public function playRole($roles)
    {
        $roles = is_array($roles) ? $roles : explode(' ', $roles);
        return !!$this->roles()->whereIn((new Role())->getTable().'.id', $roles)->count();
    }

    /**
     * Check if User plays every of given roles (array or space separated string)
     * @param string|array $roles
     * @return bool
     */
    public function playRoles($roles)
    {
        $roles = is_array($roles) ? $roles : explode(' ', $roles);
        return count($roles) === $this->roles()->whereIn((new Role())->getTable().'.id', $roles)->count();
    }

    /**
     * Check if User plays given role in model?
     * @param Model|RPAC $model
     * @param string $as
     * @return boolean
     */
    public function relatesTo(Model $model, $as)
    {
        return $model->relatedTo($this, $as);
    }

    public static function bootHasRoles()
    {
        static::saving(function (HasRoles $user) {
            if (!$user->api_token) {
                $user->api_token = Str::random(80);
            }
        });
    }

}
