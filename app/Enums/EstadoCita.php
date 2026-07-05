<?php

namespace App\Enums;

enum EstadoCita: string
{
    case PENDIENTE  = 'activa';
    case CANCELADA  = 'cancelada';
    case COMPLETADA = 'finalizada';
}