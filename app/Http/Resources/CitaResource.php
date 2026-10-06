<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'paciente'    => [
                'id' => $this->paciente->id,
                'nombre_completo' => $this->paciente->usuario->nombre_completo ?? '',
            ],
            'medico'      => [
                'id' => $this->medico->id,
                'nombre_completo' => $this->medico->usuario->nombre_completo ?? '',
            ],
            'fecha'       => $this->fecha->format('Y-m-d'),
            'hora'        => $this->hora->format('H:i'),
            'estado'      => $this->estado instanceof \BackedEnum ? $this->estado->value : $this->estado,
            'motivo'      => $this->motivo,
            'notas'       => $this->notas,
        ];
    }
}