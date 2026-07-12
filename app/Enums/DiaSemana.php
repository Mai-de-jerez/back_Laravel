<?php

namespace App\Enums;

enum DiaSemana: string
{
    case LUNES = 'lunes';
    case MARTES = 'martes';
    case MIERCOLES = 'miercoles';
    case JUEVES = 'jueves';
    case VIERNES = 'viernes';
    case SABADO = 'sabado';
    case DOMINGO = 'domingo';

    /**
     * Obtener todos los valores del enum como array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Obtener la etiqueta en español
     */
    public function label(): string
    {
        return match($this) {
            self::LUNES => 'Lunes',
            self::MARTES => 'Martes',
            self::MIERCOLES => 'Miércoles',
            self::JUEVES => 'Jueves',
            self::VIERNES => 'Viernes',
            self::SABADO => 'Sábado',
            self::DOMINGO => 'Domingo',
        };
    }
}