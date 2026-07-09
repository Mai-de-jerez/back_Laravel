<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\RolUsuario;
use App\Mail\PasswordResetMail;

class User extends Authenticatable 
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

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

    // ============ RELACIONES ============

    /**
     * Un usuario puede ser un médico
     */
    public function medico(): HasOne
    {
        return $this->hasOne(Medico::class, 'id_usuario');
    }

    /**
     * Un usuario puede ser un paciente
     */
    public function paciente(): HasOne
        {
            return $this->hasOne(Paciente::class, 'id_usuario');
        }
    
    // ============ MÉTODOS DE UTILIDAD ============

    /**
     * Verificar si el usuario es administrador
     */
    public function esAdmin(): bool
        {
            return $this->rol === RolUsuario::ADMIN;
        }

    /**
     * Verificar si el usuario es médico
     */
    public function esMedico(): bool
        {
            return $this->rol === RolUsuario::MEDICO;
        }

    /**
     * Verificar si el usuario es paciente
     */
    public function esPaciente(): bool
        {
            return $this->rol === RolUsuario::PACIENTE;
        }

    /**
     * Verificar si el usuario está activo
     */
    public function estaActivo(): bool
        {
            return $this->activo;
        }

    /**
     * Método para desactivar usuario
     */
    public function desactivar(): void
    {
        $this->update(['activo' => false]);
    }

    /**
     * Método para activar usuario
     */
    public function activar(): void
    {
        $this->update(['activo' => true]);
    }

    // ============ ACCESSORS ============

    /**
     * Sobrescribe el envío de la notificación de reset de contraseña
     * para usar nuestro Mailable personalizado (diseño oscuro/morado)
     * en vez de la notificación por defecto de Laravel.
     */
    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = config('app.frontend_url') 
            . '/auth/restablecer-password?token=' . $token 
            . '&email=' . urlencode($this->email);

        Mail::to($this->email)->send(new PasswordResetMail($resetUrl, $this->nombre));
    }

    /**
     * Obtener el nombre completo del usuario
     */
    public function getNombreCompletoAttribute(): string
        {
            return trim($this->nombre . ' ' . $this->apellidos);
        }

    /**
     * Obtener el perfil específico del usuario (médico o paciente)
     */
    public function getPerfilAttribute()
    {
        return match($this->rol) {
            RolUsuario::MEDICO => $this->medico,
            RolUsuario::PACIENTE => $this->paciente,
            default => null
        };
    }

    /**
     * Obtener el rol en formato legible
     */
    public function getRolLabelAttribute(): string
    {
        return match($this->rol) {
            RolUsuario::ADMIN => 'Administrador',
            RolUsuario::MEDICO => 'Médico',
            RolUsuario::PACIENTE => 'Paciente',
        };
    }

    // ============ SCOPES ============

    /**
     * Scope para filtrar por rol
     */
    public function scopeAdmin($query)
    {
        return $query->where('rol', RolUsuario::ADMIN);
    }

    public function scopeMedico($query)
    {
        return $query->where('rol', RolUsuario::MEDICO);
    }

    public function scopePaciente($query)
    {
        return $query->where('rol', RolUsuario::PACIENTE);
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para buscar por email
     */
    public function scopePorEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope para usuarios inactivos
     */
    public function scopeInactivo($query)
    {
        return $query->where('activo', false);
    }
}


