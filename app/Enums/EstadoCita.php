<?php

namespace App\Enums;

enum EstadoCita: string
{
    case ACTIVA  = 'activa';
    case CANCELADA  = 'cancelada';
    case FINALIZADA = 'finalizada';
    case NO_PRESENTADO = 'no_presentado';
}