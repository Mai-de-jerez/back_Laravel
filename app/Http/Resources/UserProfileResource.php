<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\FileUploadService;

class UserProfileResource extends JsonResource
{
    /**
     * Transforma el recurso en un array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellidos' => $this->apellidos,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'foto_url' => app(FileUploadService::class)->obtenerUrl($this->foto),
            'rol' => $this->rol,
            'activo' => (bool) $this->activo,
            
            'medico' => $this->when($this->relationLoaded('medico') && $this->medico, function () {
                return [
                    'id' => $this->medico->id,
                    'numero_colegiado' => $this->medico->numero_colegiado,
                    'especialidad' => $this->medico->especialidad,                 
                    'centro' => $this->medico->centro
                ];
            }),

            'paciente' => $this->when($this->relationLoaded('paciente') && $this->paciente, function () {
                return [
                    'id' => $this->paciente->id,
                    'numero_tarjeta' => $this->paciente->numero_tarjeta,
                    'compania' => $this->paciente->compania,
                ];
            }),
        ];
    }
}