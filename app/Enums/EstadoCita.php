<?php

namespace App\Enums;

enum EstadoCita: string
{
    case ACTIVA  = 'activa';
    case CANCELADA  = 'cancelada';
    case FINALIZADA = 'finalizada';
}