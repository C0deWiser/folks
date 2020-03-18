<?php

namespace Codewiser\Rpac\Example;

use Illuminate\Database\Eloquent\Builder;

class Post extends \Illuminate\Database\Eloquent\Model
{
    public function scopeRelationshipAuthor(Builder $query, $user)
    {

    }
}