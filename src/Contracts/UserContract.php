<?php

namespace Codewiser\Folks\Contracts;

interface UserContract
{
    public function getId();

    public function getName();

    public function getEmail();
}
