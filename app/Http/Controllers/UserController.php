<?php

namespace App\Http\Controllers;

use App\Models\User; 
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\UserProfileResource;
use App\Http\Requests\ActualizarPerfilRequest;
use App\Http\Requests\ActualizarUsuarioRequest;
use App\Http\Requests\CrearUsuarioRequest;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    /**
     * Endpoint para obtener el perfil del usuario autenticado con sus relaciones.
     */
    public function perfil(Request $request): JsonResponse
    {
        $usuario = $this->userService->obtenerConPerfil($request->user()->id);

        return response()->json([
            'usuario' => new UserProfileResource($usuario)
        ], 200);
    }

    /**
     * Endpoint para actualizar el perfil del usuario autenticado.
     */
    public function actualizar(ActualizarPerfilRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $datosUsuario = $request->validated();
        $foto = $request->file('foto');
        unset($datosUsuario['foto']);

        $usuarioActualizado = $this->userService->actualizarPerfil(
            $usuario->id,
            $datosUsuario,
            $foto
        );

        Log::info('Perfil actualizado por el propio usuario', [
            'user_id' => $usuario->id,
            'campos_actualizados' => array_keys($datosUsuario),
        ]);

        return response()->json([
            'mensaje' => 'Perfil actualizado correctamente',
            'usuario' => new UserProfileResource($usuarioActualizado)
        ], 200);
    }

    /**
     * Listar usuarios (solo admin)
     */
    public function listarUsuarios(Request $request): JsonResponse
    {
        $filtros = $request->only(['id', 'rol', 'nombre', 'apellidos']);

        $usuarios = $this->userService->listarUsuarios($filtros);

        return response()->json($usuarios, 200);
    }

    /**
     * Obtener detalle de un usuario por ID (solo admin)
     */
    public function mostrarUsuario(User $usuario): JsonResponse
                              
    {
        $usuario->load(['paciente', 'medico']); 

        return response()->json([
            'usuario' => new UserProfileResource($usuario)
        ], 200);
    }

    /**
     * Crear un nuevo usuario (admin)
     */
    public function crearUsuario(CrearUsuarioRequest $request): JsonResponse
    {
        $usuario = $this->userService->crearUsuario(
            $request->validated(),
            $request->file('foto')
        );

        Log::info('Usuario creado por admin', [
            'admin_id' => $request->user()->id,
            'usuario_creado_id' => $usuario->id,
            'rol' => $usuario->rol->value,
        ]);

        return response()->json([
            'mensaje' => 'Usuario creado correctamente',
            'usuario' => new UserProfileResource($usuario)
        ], 201);
    }

    /**
     * Actualizar un usuario (admin)
     */
    public function actualizarUsuario(ActualizarUsuarioRequest $request, User $usuario): JsonResponse                                                              
    {
        $datosUsuario = $request->validated();
        $foto = $request->file('foto');
        unset($datosUsuario['foto']);

        $usuarioActualizado = $this->userService->actualizarUsuario(
            $usuario,  
            $datosUsuario,
            $foto
        );

        Log::info('Usuario actualizado por admin', [
            'admin_id' => $request->user()->id,
            'usuario_actualizado_id' => $usuario->id,
            'campos_actualizados' => array_keys($datosUsuario),
        ]);

        return response()->json([
            'mensaje' => 'Usuario actualizado correctamente',
            'usuario' => new UserProfileResource($usuarioActualizado)
        ], 200);
    }

    /**
     * Dar de baja a un usuario (admin)
     */
    public function darDeBajaUsuario(Request $request, User $usuario): JsonResponse
    {
        $usuarioActualizado = $this->userService->darDeBajaUsuario($usuario);

        return response()->json([
            'mensaje' => 'Usuario dado de baja correctamente',
            'usuario' => new UserProfileResource($usuarioActualizado),
        ], 200);
    }
}


