<?php

namespace App\Exceptions\Auth;

use Exception;

class InactiveUserException extends Exception
{
    public function __construct()
    {
        parent::__construct('Usuario inactivo', 403);
    }
}