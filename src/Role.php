<?php

namespace Codewiser\Rpac;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User;

/**
 * Class Role
 * @package Codewiser\Rpac
 * 
 * @property string $name
 * @property string $slug
 * @property string $description
 */
class Role extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'slug', 'description'];

    /**
     * Get list of all defined roles (slugs)
     * @return array|string[]
     */
    public static function allSlugs()
    {
        return array_merge(['any', 'guest'], static::all()->pluck('slug')->toArray());
    }

    /**
     * Role belongs to many users.
     *
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
