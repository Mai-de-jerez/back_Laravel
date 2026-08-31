<?php

namespace App\Services;

use App\Models\Especialidad;
use Illuminate\Database\Eloquent\Collection;

class EspecialidadService
{
    /**
     * Obtener todas las especialidades ordenadas por nombre
     */
    public function listar(): Collection
    {
        return Especialidad::orderBy('nombre')->get();
    }

    /**
     * Obtener los médicos de una especialidad concreta limpios
     */
    public function listarMedicosPorEspecialidad(Especialidad $especialidad): array
    {
        $medicos = $especialidad->medicos()->with('usuario')->get()->map(function ($medico) {
            return [
                'id' => $medico->id,
                'nombre_completo' => $medico->usuario->nombre_completo ?? '',
            ];
        });

        return [
            'especialidad' => [
                'id' => $especialidad->id,
                'nombre' => $especialidad->nombre,
            ],
            'medicos' => $medicos
        ];
    }
}