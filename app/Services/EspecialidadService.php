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
}