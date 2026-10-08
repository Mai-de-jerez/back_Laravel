<?php

namespace App\Services;

use App\Models\User;
use App\Models\Medico;              
use App\Models\Paciente; 
use App\Services\FileUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
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
            Log::warning('Intento de consulta de perfil de usuario inexistente', ['user_id' => $userId]);
            throw new NotFoundHttpException('Usuario no encontrado');
        }
        return $user;
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
     * Actualizar perfil de usuario y sus relaciones con las tablas paciente/médico
     * @param int $userId parametro que pasa el id del susodicho
     * @param array $datosUsuario parametro que pasa los datos del usuario a actualizar
     * @param UploadedFile|null $foto parametro que pasa la foto del usuario a actualizar
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
                $datosUsuario['password'] = Hash::make($datosUsuario['password']);
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

            Log::info('Perfil de usuario actualizado', ['user_id' => $user->id]);
            return $user;
        });
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

        try {
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
                if ($usuario->esMedico()) { 
                    Medico::create([
                        'id_usuario' => $usuario->id,
                        'numero_colegiado' => $datos['numero_colegiado'],
                        'id_especialidad' => $datos['id_especialidad'],
                        'id_centro' => $datos['id_centro'],
                    ]);
                }

                if ($usuario->esPaciente()) { 
                    Paciente::create([
                        'id_usuario' => $usuario->id,
                        'numero_tarjeta' => $datos['numero_tarjeta'],
                        'compania' => $datos['compania'],
                    ]);
                }

                $usuario->load(['medico', 'paciente']);

                Log::info('Usuario creado por admin', [ 
                    'admin_id' => Auth::id(),
                    'nuevo_usuario_id' => $usuario->id,
                    'rol' => $usuario->rol,
                ]);

                return $usuario;
            });

        } catch (\Exception $e) {
            try {
                $this->fileUploadService->eliminarFoto($rutaFoto);
            } catch (\Exception $eLimpieza) {
                Log::error('Fallo limpiando foto tras error de creación de usuario: ' . $eLimpieza->getMessage(), [
                    'ruta' => $rutaFoto
                ]);
            }

            Log::error('Error al crear usuario (admin): ' . $e->getMessage(), [
                'email' => $datos['email'] ?? 'unknown'
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar un usuario (admin)
     * @param User $user Modelo de usuario ya cargado (Route Model Binding)
     * @param array $datos Datos a actualizar
     * @param UploadedFile|null $foto Foto del usuario
     * @return User Usuario actualizado con sus relaciones
     */
    public function actualizarUsuario(User $user, array $datos, ?UploadedFile $foto = null): User
    {
        return DB::transaction(function () use ($user, $datos, $foto) {

            // Si la contraseña viene, la encriptamos
            if (!empty($datos['password'])) {
                $datos['password'] = Hash::make($datos['password']);
            } else {
                unset($datos['password']);
            }

            // Si la foto viene, la actualizamos
            if ($foto) {
                $rutaAnterior = $user->foto;
                $datos['foto'] = $this->fileUploadService->actualizarFoto($foto, $rutaAnterior);
            }

            // Actualizar tabla 'usuarios'
            $user->update($datos);

            // Si el usuario es paciente, actualizar sus datos de paciente
            if ($user->esPaciente()) {
                if (isset($datos['numero_tarjeta']) || isset($datos['compania'])) {
                    if ($user->paciente) {
                        $user->paciente->update([
                            'numero_tarjeta' => $datos['numero_tarjeta'] ?? $user->paciente->numero_tarjeta,
                            'compania' => $datos['compania'] ?? $user->paciente->compania,
                        ]);
                    } else {
                        Log::warning('Usuario paciente sin relación paciente', ['user_id' => $user->id]);
                        throw new NotFoundHttpException('No se encontró el perfil de paciente asociado a este usuario.');
                    }
                }
            }

            // Si el usuario es médico, actualizar sus datos de médico
            if ($user->esMedico()) {
                if (isset($datos['numero_colegiado']) || isset($datos['id_centro']) || isset($datos['id_especialidad'])) {
                    if ($user->medico) {
                        $user->medico->update([
                            'numero_colegiado' => $datos['numero_colegiado'] ?? $user->medico->numero_colegiado,
                            'id_especialidad' => $datos['id_especialidad'] ?? $user->medico->id_especialidad,
                            'id_centro' => $datos['id_centro'] ?? $user->medico->id_centro,
                        ]);
                    } else {
                        Log::warning('Usuario médico sin relación médico', ['user_id' => $user->id]);
                        throw new NotFoundHttpException('No se encontró el perfil de médico asociado a este usuario.');
                    }
                }
            }

            $user->load(['medico', 'paciente']);

            Log::info('Usuario actualizado por admin', [
                'usuario_actualizado_id' => $user->id,
                'rol' => $user->rol,
            ]);

            return $user;
        });
    }

    /**
     * Dar de baja a un usuario en la DB
     */
    public function darDeBajaUsuario(User $usuario): User
    {
        $usuario->desactivar();

        Log::info('Usuario dado de baja', ['usuario_id' => $usuario->id]);

        return $usuario;
    }

    
    /**
     * Obtener estadísticas de usuarios
     * @return array retorna un array con las estadísticas de usuarios
     */
    public function obtenerEstadisticas(): array
    {
        $stats = User::selectRaw("
            COUNT(*) as total,
            SUM(activo = 1) as activos,
            SUM(activo = 0) as inactivos,
            SUM(rol = 'admin') as admins,
            SUM(rol = 'medico') as medicos,
            SUM(rol = 'paciente') as pacientes
        ")->first();

        return [
            'total'      => (int) $stats->total,
            'activos'    => (int) $stats->activos,
            'inactivos'  => (int) $stats->inactivos,
            'admins'     => (int) $stats->admins,
            'medicos'    => (int) $stats->medicos,
            'pacientes'  => (int) $stats->pacientes,
        ];
    }
}

