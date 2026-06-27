<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medico extends Model
{
    use HasFactory;

    protected $table = 'medicos';

    protected $fillable = [
        'id_usuario',
        'id_especialidad',
        'numero_colegiado',
        'descripcion'
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    // RELACIONES DE ELOQUENT

    /**
     * Un medico pertenece a un usuario 
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * Un medico pertenece a una especialidad
     */
    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad');
    }

    /**
     * Un médico puede tener muchas citas
     */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_medico');
    }

    /**
     * Un médico puede tener muchos horarios
     */
    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'id_medico');
    }
}