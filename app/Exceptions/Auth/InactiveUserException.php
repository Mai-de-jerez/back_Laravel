<?php

namespace App\Exceptions\Auth;

use Exception;


class InactiveUserException extends Exception
{
    public function __construct(string $mensaje = 'Usuario inactivo')
    {
        parent::__construct($mensaje, 403);
    }
}