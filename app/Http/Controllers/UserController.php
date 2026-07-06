<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\UserProfileResource;
use App\Http\Requests\ActualizarPerfilRequest;
use App\Http\Requests\CrearUsuarioRequest;
use App\Services\UserService;

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
        ]);
    }

    /**
     * Endpoint para actualizar el perfil del usuario autenticado.
     */   
    public function actualizar(ActualizarPerfilRequest $request): JsonResponse
    {
        $usuario = $request->user();

        $datosUsuario = $request->validated();

        // extraemos la foto de los datos validados
        $foto = $request->file('foto');
        unset($datosUsuario['foto']);

        // datos de relación con paciente
        $datosRelacion = [];
        if ($usuario->paciente) {
            $datosRelacion = [
                'numero_tarjeta' => $datosUsuario['numero_tarjeta'] ?? null,
                'compania' => $datosUsuario['compania'] ?? null,
            ];
            // eliminamos estos campos de los datos para que no se intenten actualizar en la tabla 'usuarios'
            unset($datosUsuario['numero_tarjeta']);
            unset($datosUsuario['compania']);
        }

        $usuarioActualizado = $this->userService->actualizarPerfil(
            $usuario->id,
            $datosUsuario,
            $foto,
            $datosRelacion
        );

        return response()->json([
            'mensaje' => 'Perfil actualizado correctamente',
            'usuario' => new UserProfileResource($usuarioActualizado)
        ]);
    }

    /**
     * Listar usuarios (solo admin)
     */
    public function listarUsuarios(Request $request): JsonResponse
    {
        $filtros = $request->only(['id','rol', 'nombre', 'apellidos']);
        
        $usuarios = $this->userService->listarUsuarios($filtros);
        
        return response()->json($usuarios);
    }

    /**
     * Obtener detalle de un usuario por ID (solo admin)
     */
    public function mostrarUsuario(int $id): JsonResponse
    {
        $usuario = $this->userService->obtenerConPerfil($id);
        
        return response()->json([
            'usuario' => new UserProfileResource($usuario)
        ]);
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

        return response()->json([
            'mensaje' => 'Usuario creado correctamente',
            'usuario' => new UserProfileResource($usuario)
        ], 201);
    }
}
