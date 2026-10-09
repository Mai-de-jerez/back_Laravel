<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_completo' => $this->usuario?->nombre_completo ?? '',
            'numero_colegiado' => $this->numero_colegiado,
            'especialidad' => [
                'id' => $this->especialidad?->id,
                'nombre' => $this->especialidad?->nombre,
            ],
            'centro' => [
                'id' => $this->centro?->id,
                'nombre' => $this->centro?->nombre,
            ],
        ];
    }
}