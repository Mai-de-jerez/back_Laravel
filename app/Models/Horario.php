<?php

namespace App\Models;

use App\Enums\DiaSemana; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Horario extends Model
{
    use HasFactory;

    protected $table = 'horarios';

    protected $fillable = [
        'id_medico',
        'dia_semana',
        'hora_inicio',
        'hora_fin'
    ];

    /**
     * Castea los atributos a tipos específicos
     */
    protected function casts(): array
    {
        return [
            'dia_semana'  => DiaSemana::class,  
            'hora_inicio' => 'datetime:H:i',  
            'hora_fin' => 'datetime:H:i', 
        ];
    }

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    /**
     * Un horario pertenece a un médico
     */
    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico');
    }
}