<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paciente extends Model
{
    use HasFactory;

    protected $table = 'pacientes';

    protected $fillable = [
        'id_usuario',
        'numero_tarjeta',
        'compania'
    ];


    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    // RELACIONES DE ELOQUENT

    /**
     * Un paciente pertenece a un usuario 
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * Un paciente puede tener muchas citas
     */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_paciente');
    }
}