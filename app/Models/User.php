<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Enums\RolUsuario;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'apellidos',
        'email',
        'password',
        'telefono',
        'foto',
        'rol',
        'activo'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'rol' => RolUsuario::class,
        ];
    }

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_modificacion';

    /**
     * Obtener el identificador que se almacenará en el JWT
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Obtener las reclamaciones personalizadas que se agregarán al JWT
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Un usuario puede ser un médico
     */
    public function medico(): HasOne
    {
        return $this->hasOne(Medico::class, 'id_usuario');
    }

    /**
     * Un usuario (como paciente) puede tener muchas citas
     */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_paciente');
    }
}


