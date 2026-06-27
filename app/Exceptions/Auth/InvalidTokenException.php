<?php

namespace App\Exceptions\Auth;

use Exception;

class InvalidTokenException extends Exception
{
    public function __construct(string $mensaje = 'Token inválido o expirado')
    {
        parent::__construct($mensaje, 400);
    }
}