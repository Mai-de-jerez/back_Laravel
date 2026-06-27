<?php

namespace App\Enums;

enum RolUsuario: string
{
    case ADMIN = 'admin';
    case MEDICO = 'medico';
    case PACIENTE = 'paciente';
}