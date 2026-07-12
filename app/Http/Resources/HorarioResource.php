<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HorarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_medico' => $this->id_medico,
            'dia_semana' => $this->dia_semana,
            'hora_inicio' => $this->hora_inicio?->format('H:i'),
            'hora_fin' => $this->hora_fin?->format('H:i'),
            
            // solo si es necesario para el admin, no para el médico
            'fecha_creacion' => $this->when($request->user()?->esAdmin(), $this->fecha_creacion),
            'fecha_modificacion' => $this->when($request->user()?->esAdmin(), $this->fecha_modificacion),

            // nombre del médico, y no todos sus datos
            'medico_nombre' => $this->whenLoaded('medico', function () {
                return $this->medico->usuario->nombre_completo ?? null;
            }),
        ];
    }
}
