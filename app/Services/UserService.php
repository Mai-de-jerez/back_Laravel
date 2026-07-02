<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserService
{
    /**
     * Obtener usuario por ID con sus relaciones
     */

    public function obtenerConPerfil(int $userId): User
    {
        $user = User::with(['medico', 'paciente'])->find($userId);
        
        if (!$user) {
            throw new NotFoundHttpException('Usuario no encontrado');
        }
        
        return $user;
    }

    /**
     * Cambiar rol de usuario
     */
    public function cambiarRol(int $userId, string $nuevoRol): User
    {
        $user = User::findOrFail($userId);
        $user->rol = $nuevoRol;
        $user->save();

        Log::info('Rol de usuario cambiado', [
            'user_id' => $userId,
            'nuevo_rol' => $nuevoRol
        ]);

        return $user;
    }

    /**
     * Obtener estadísticas de usuarios
     */
    public function obtenerEstadisticas(): array
    {
        return [
            'total' => User::count(),
            'activos' => User::activo()->count(),
            'inactivos' => User::inactivo()->count(),
            'admins' => User::admin()->count(),
            'medicos' => User::medico()->count(),
            'pacientes' => User::paciente()->count(),
        ];
    }
}

