<?php

namespace App\Services;

use App\Models\Medico;
use Illuminate\Support\Collection;
class MedicoService
{
    /**
     * Listar médicos con filtros opcionales por especialidad y/o centro.
     *
     * @param array $filtros  ['id_especialidad' => int|null, 'id_centro' => int|null]
     * @return Collection
     */
    public function listarMedicos(array $filtros = []): Collection
    {
        $query = Medico::with(['usuario', 'especialidad', 'centro']);

        if (!empty($filtros['id_especialidad'])) {
            $query->where('id_especialidad', $filtros['id_especialidad']);
        }

        if (!empty($filtros['id_centro'])) {
            $query->where('id_centro', $filtros['id_centro']);
        }

        return $query->orderBy('id')->get();
    }
}