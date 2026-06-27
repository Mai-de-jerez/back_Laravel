<?php

namespace App\Exceptions\Auth;

use Exception;

class EmailNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Email no encontrado', 404);
    }
}