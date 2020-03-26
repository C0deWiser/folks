<?php

namespace Codewiser\Rpac;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User;

/**
 * Class Role
 * @package Codewiser\Rpac
 *
 * @property string $id
 * @property string $name
 * @property string $description
 */
class Role extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'name', 'description'];

    /**
     * Get list of all defined roles (slugs)
     * @return array|string[]
     */
    public static function allSlugs()
    {
        return array_merge(['any', 'guest'], static::all()->modelKeys());
    }

    /**
     * Role belongs to many users
     *
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(
            config('rpac.models.user'),
            'role_user',
            'role',
            'user_id'
        )->withTimestamps();
    }
}
