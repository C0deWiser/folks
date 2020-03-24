<?php

namespace Codewiser\Rpac\Example;

use Codewiser\Rpac\Traits\RPAC;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

class Post extends \Illuminate\Database\Eloquent\Model
{
    use RPAC;

    public function relationships()
    {
        return [
            '_hasOne' => function (Builder $query, Model $user) {
                return $query->where(
                    $this->_hasOne()->getLocalKeyName(),
                    $user->getAttribute(
                        $this->_hasOne()->getForeignKeyName()
                    )
                );
            },
            '_belongsTo' => function (Builder $query, Model $user) {
                return $query->where(
                    $this->_belongsTo()->getForeignKeyName(),
                    $user->getAttribute(
                        $this->_belongsTo()->getOwnerKeyName()
                    )
                );
            },
            '_belongsToMany' => function (Builder $query, Model $user) {
                return $query->where($this->_belongsToMany()->getParentKeyName(), $user->getKey());
            },
            '_hasMany' => function (Builder $query, Model $user) {
                return $query->whereKey($user->getAttribute($this->_hasMany()->getForeignKeyName()));
            },
        ];
    }

    public function _hasOne()
    {
        return $this->morphedByMany(User::class);
    }

    public function _belongsTo()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
    public function _belongsToMany()
    {
        return $this->belongsToMany(User::class);
    }
    public function _hasMany()
    {
        return $this->hasMany(User::class, 'post_id');
    }
    public function scopeRelationshipAuthor(Builder $query, Model $user)
    {
        $query->where('author_id', $user->getKey());
    }
}