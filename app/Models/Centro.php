<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Centro extends Model
{
    use HasFactory;

    protected $table = 'centros';

    protected $fillable = [
        'nombre',
        'direccion',
        'ciudad',
        'provincia',
    ];

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    // RELACIONES DE ELOQUENT

    /**
     * Un centro tiene muchos médicos
     */
    public function medicos(): HasMany
    {
        return $this->hasMany(Medico::class, 'id_centro');
    }
}