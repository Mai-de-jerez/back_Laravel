<?php

namespace App\Services;

use App\Models\User;
use App\Models\Medico;              
use App\Models\Paciente; 
use App\Enums\RolUsuario;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Facades\Hash; 

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


    public function actualizarPerfil(int $userId, array $datosUsuario, ?UploadedFile $foto = null): User
    {
        return DB::transaction(function () use ($userId, $datosUsuario, $foto) {

            $user = User::find($userId);
            if (!$user) {
                throw new NotFoundHttpException('Usuario no encontrado');
            }

            if (!empty($datosUsuario['password'])) {
                $datosUsuario['password'] = bcrypt($datosUsuario['password']);
            } else {
                unset($datosUsuario['password']);
            }

            if ($foto) {
                $rutaAnterior = $user->foto;
                $datosUsuario['foto'] = $this->fileUploadService->actualizarFoto($foto, $rutaAnterior);
            }

            // Separar campos específicos de paciente (solo si vinieron en la petición)
            $datosRelacion = [];
            foreach (['numero_tarjeta', 'compania'] as $campo) {
                if (array_key_exists($campo, $datosUsuario)) {
                    $datosRelacion[$campo] = $datosUsuario[$campo];
                    unset($datosUsuario[$campo]);
                }
            }

            $user->update($datosUsuario);

            if (!empty($datosRelacion) && $user->paciente) {
                $user->paciente->update($datosRelacion);
            }

            $user->load(['medico', 'paciente']);
            return $user;
        });
    }

    /**
     * Listar usuarios con filtros opcionales y paginación
     * @param array $filtros parametro que pasa los filtros para listar usuarios
     * @return array retorna un array con los usuarios filtrados y paginados
     */
    public function listarUsuarios(array $filtros = []): array
    {
        $paginator = User::query()
            ->select(['id', 'nombre', 'apellidos', 'email', 'rol', 'activo', 'fecha_creacion', 'fecha_modificacion'])
            ->when(!empty($filtros['id']), fn($q) => $q->where('id', $filtros['id']))
            ->when(!empty($filtros['rol']), fn($q) => $q->where('rol', $filtros['rol']))
            ->when(!empty($filtros['nombre']), fn($q) => $q->where('nombre', 'LIKE', $filtros['nombre'] . '%'))
            ->when(!empty($filtros['apellidos']), fn($q) => $q->where('apellidos', 'LIKE', $filtros['apellidos'] . '%'))
            ->latest('fecha_creacion')
            ->paginate(15);

        return [
            'usuarios' => $paginator->items(),
            'pagina_actual' => $paginator->currentPage(),
            'ultima_pagina' => $paginator->lastPage(),
            'por_pagina' => $paginator->perPage(),
            'total' => $paginator->total(),
            ];
    }

    
    /**
     * Crear un nuevo usuario con su relación correspondiente (paciente o médico)
     * @param array $datos parametro que pasa los datos del usuario a crear
     * @param UploadedFile|null $foto parametro que pasa la foto del usuario a crear
     * @return User retorna el usuario creado con sus relaciones medico y paciente
     */
    public function crearUsuario(array $datos, ?UploadedFile $foto = null): User
    {
        $rutaFoto = $foto
            ? $this->fileUploadService->subirFoto($foto)
            : $this->fileUploadService->getFotoDefault();

        return DB::transaction(function () use ($datos, $rutaFoto) {
            
            $usuario = User::create([
                'nombre' => $datos['nombre'],
                'apellidos' => $datos['apellidos'],
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'telefono' => $datos['telefono'] ?? null,
                'foto' => $rutaFoto,
                'rol' => $datos['rol'],
                'activo' => true,
            ]);

            // creamos la relación correspondiente según el rol del usuario
            if ($datos['rol'] === 'medico') {
                Medico::create([
                    'id_usuario' => $usuario->id,
                    'numero_colegiado' => $datos['numero_colegiado'],
                    'id_especialidad' => $datos['id_especialidad'],
                ]);
            }

            if ($datos['rol'] === 'paciente') {
                Paciente::create([
                    'id_usuario' => $usuario->id,
                    'numero_tarjeta' => $datos['numero_tarjeta'],
                    'compania' => $datos['compania'],
                ]);
            }

            $usuario->load(['medico', 'paciente']);
            return $usuario;
        });
    }


    /**
     * Actualizar un usuario (admin)
     * @param int $userId parametro que pasa el id del usuario a actualizar
     * @param array $datos parametro que pasa los datos del usuario a actualizar
     * @param UploadedFile|null $foto parametro que pasa la foto del usuario a actualizar
     * @return User retorna el usuario actualizado con sus relaciones medico y paciente
     */
    public function actualizarUsuario(int $userId, array $datos, ?UploadedFile $foto = null): User
    {
        return DB::transaction(function () use ($userId, $datos, $foto) {
            
            $user = User::find($userId);

            if (!$user) {
                throw new NotFoundHttpException('Usuario no encontrado');
            }

            // Si la contraseña viene, la encriptamos
            if (!empty($datos['password'])) {
                $datos['password'] = bcrypt($datos['password']);
            } else {
                unset($datos['password']);
            }

            // Si la foto viene, la actualizamos
            if ($foto) {
                $rutaAnterior = $user->foto;
                $datos['foto'] = $this->fileUploadService->actualizarFoto($foto, $rutaAnterior);
            }

            // Actualizar tabla 'usuarios' (SIN 'rol')
            $user->update($datos);

            // Si el usuario es paciente, actualizar sus datos de paciente
            if ($user->esPaciente()) {
                // verificamos que la compañia y el número de tarjeta estén presentes en los datos
                if (isset($datos['numero_tarjeta']) || isset($datos['compania'])) {
                    if ($user->paciente) {
                        $user->paciente->update([
                            'numero_tarjeta' => $datos['numero_tarjeta'] ?? $user->paciente->numero_tarjeta,
                            'compania' => $datos['compania'] ?? $user->paciente->compania,
                        ]);
                    } else {
                        Log::warning('Usuario paciente sin relación paciente', ['user_id' => $user->id]);
                    }
                }
            }

            // si el usuario es médico, actualizar sus datos de médico
            if ($user->esMedico()) {
                if (isset($datos['numero_colegiado'])) {
                    if ($user->medico) {
                        $user->medico->update([
                            'numero_colegiado' => $datos['numero_colegiado'],
                            'id_especialidad' => $datos['id_especialidad'] ?? $user->medico->id_especialidad,
                        ]);
                    } else {
                        Log::warning('Usuario médico sin relación médico', ['user_id' => $user->id]);
                    }
                }
            }

            $user->load(['medico', 'paciente']);
            return $user;
        });
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

