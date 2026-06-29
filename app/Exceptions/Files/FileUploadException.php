<?php

namespace App\Exceptions\Files;

use Exception;

// class FileUploadException extends Exception
// {
//     public function __construct(string $mensaje = 'No se pudo subir el archivo')
//     {
//         parent::__construct($mensaje, 500);
//     }
// }

class FileUploadException extends Exception
{
    public function __construct(string $mensaje = 'No se pudo subir el archivo')
    {
        parent::__construct($mensaje, 422);  // 👈 422 en lugar de 500
    }
}