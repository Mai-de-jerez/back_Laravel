<?php

namespace App\Exceptions;

use Exception;

class FileUploadException extends Exception
{
    public function __construct(string $mensaje = 'No se pudo subir el archivo')
    {
        parent::__construct($mensaje, 500);
    }
}