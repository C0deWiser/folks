<?php

namespace Codewiser\Rpac\Example;

class PostPolicy extends \Codewiser\Rpac\Policies\RpacPolicy
{

    protected $relationships = ['author'];

    /**
     * Policy need to know Model it works with
     * @return string
     */
    public function model()
    {
        Post::class;
    }
}