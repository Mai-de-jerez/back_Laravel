<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\EstadoCita; 

class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';

    protected $fillable = [
        'id_paciente',
        'id_medico',
        'fecha',
        'hora',
        'estado',
        'motivo',
        'notas'
    ];

    /**
     * Castea los atributos a tipos específicos
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoCita::class,
            'fecha'  => 'date',
            'hora'   => 'datetime:H:i',
        ];
    }

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    /**
     * La cita pertenece a un paciente 
     */
    public function paciente(): BelongsTo
        {
            return $this->belongsTo(Paciente::class, 'id_paciente');
        }

    /**
     * La cita pertenece a un médico
     */
    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico');
    }
}