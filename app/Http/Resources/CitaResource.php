<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitaResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        return [
            'id'          => $this->id,
            'id_paciente' => $this->id_paciente,
            'id_medico'   => $this->id_medico,
            'fecha'       => $this->fecha,
            'hora'        => $this->hora,
            'estado'      => $this->estado instanceof \BackedEnum ? $this->estado->value : $this->estado,
            'motivo'      => $this->motivo,
            'notas'       => $this->notas,
        ];
    }
}
