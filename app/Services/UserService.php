<?php

namespace App\Services;

use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserService
{
    // Inyectamos FileUploadService para manejar la subida/actualización/eliminación de fotos
    public function __construct(
        private FileUploadService $fileUploadService 
    ) {}

    /**
     * Obtener usuario por ID con sus relaciones con las tablas paciente/médico
     * @param int $userId parametro que pasa el id del susodicho
     * @return User retorna el usuario con sus relaciones medico y paciente 
     * @throws NotFoundHttpException si el usuario no existe lanzamos mi excepción personalizada
     */
    public function obtenerConPerfil(int $userId): User
    {
        $user = User::with(['medico', 'paciente'])->find($userId);
        // no usamos findOrFail() porque quiero lanzar mi propia excepción personalizada 
        if (!$user) {
            throw new NotFoundHttpException('Usuario no encontrado');
        }
        
        return $user;
    }

    /**
     * Actualizar perfil de usuario y sus relaciones con las tablas paciente/médico
     * @param int $userId parametro que pasa el id del susodicho
     * @param array $datosUsuario parametro que pasa los datos del usuario a actualizar
     * @param UploadedFile|null $foto parametro que pasa la foto del usuario a actualizar
     * @param array $datosRelacion parametro que pasa los datos de la relación paciente/médico
     * @return User retorna el usuario actualizado con sus relaciones medico y paciente
     */
    public function actualizarPerfil(int $userId, array $datosUsuario, ?UploadedFile $foto = null, array $datosRelacion = []): User
    {
        // Buscamos al usuario 
        return DB::transaction(function () use ($userId, $datosUsuario, $foto, $datosRelacion) {
            $user = User::findOrFail($userId);

            // Si la contraseña viene, la encriptamos, si no, la eliminamos del array para que no se actualice
            if (!empty($datosUsuario['password'])) {
                $datosUsuario['password'] = bcrypt($datosUsuario['password']);
            } else {
                unset($datosUsuario['password']);
            }

            // si la foto viene, usamos el FileUploadService para actualizarla y obtener la nueva ruta
            if ($foto) {
                $rutaAnterior = $user->foto;
                $datosUsuario['foto'] = $this->fileUploadService->actualizarFoto($foto, $rutaAnterior);
            }

            // Actualizar tabla 'usuarios'
            $user->update($datosUsuario);

            // Actualizar tabla 'pacientes' (solo si vienen datos)
            if (!empty($datosRelacion)) {
                // Si el usuario tiene relación con paciente, actualizamos
                if ($user->paciente) {
                    $user->paciente->update($datosRelacion);
                }
            }

            return $user;
        });
    }

    /**
     * Cambiar rol de usuario
     * @param int $userId parametro que pasa el id del susodicho
     * @param string $nuevoRol parametro que pasa el nuevo rol del usuario
     * @return $User retorna el usuario con el nuevo rol
     * @throws \InvalidArgumentException si el rol no es válido
     */  
    public function cambiarRol(int $userId, string $nuevoRol): User
    {
        if (!RolUsuario::tryFrom($nuevoRol)) {
            throw new \InvalidArgumentException('Rol no válido');
        }
        
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
     * @return array retorna un array con las estadísticas de usuarios
     */
    public function obtenerEstadisticas(): array
    {
        return [
            // count() nos devuelve el total de usuarios, los activos, inactivos, admins, medicos y pacientes
            'total' => User::count(), 
            'activos' => User::activo()->count(),
            'inactivos' => User::inactivo()->count(),
            'admins' => User::admin()->count(),
            'medicos' => User::medico()->count(),
            'pacientes' => User::paciente()->count(),
        ];
    }
}

