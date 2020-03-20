<?php

namespace Codewiser\Rpac\Example;

use Codewiser\Rpac\Traits\Permissions;
use Illuminate\Database\Eloquent\Builder;

class Post extends \Illuminate\Database\Eloquent\Model
{
    use Permissions;
    public function scopeRelationshipAuthor(Builder $query, $user)
    {

    }
}