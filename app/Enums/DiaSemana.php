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

    /**
     * Equivalente numérico de MySQL DAYOFWEEK(): 1=domingo, 2=lunes ... 7=sabado.
     * Confirmado con DAYOFWEEK(NOW())=2 en lunes....
     */
    public function numeroMysql(): int
    {
        return match($this) {
            self::DOMINGO => 1,
            self::LUNES => 2,
            self::MARTES => 3,
            self::MIERCOLES => 4,
            self::JUEVES => 5,
            self::VIERNES => 6,
            self::SABADO => 7,
        };
    }

    /**
     * Traduce una fecha concreta a su DiaSemana correspondiente.
     * isoWeekday(): 1=lunes ... 7=domingo (coincide con el orden de los cases).
     */
    public static function fromFecha(\Carbon\Carbon $fecha): self
    {
        return match($fecha->isoWeekday()) {
            1 => self::LUNES,
            2 => self::MARTES,
            3 => self::MIERCOLES,
            4 => self::JUEVES,
            5 => self::VIERNES,
            6 => self::SABADO,
            7 => self::DOMINGO,
        };
    }
}