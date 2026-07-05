<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\UserProfileResource;

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
    public function actualizar(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $datosUsuario = $request->validate([
            'nombre'    => 'sometimes|string|min:3|max:100|regex:/^[\pL\s]+$/u',
            'apellidos' => 'sometimes|string|min:3|max:150|regex:/^[\pL\s]+$/u',
            'email'     => 'sometimes|email|unique:usuarios,email,' . $usuario->id,
            'telefono'  => 'nullable|string|max:20',
            'foto'      => 'nullable|image|max:2048',
            'password'  => ['sometimes', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()],
        ]);
        // Solo si es PACIENTE permitimos editar sus datos especiales
        $datosRelacion = [];
        if ($usuario->paciente) {
            $datosRelacion = $request->validate([
                'numero_tarjeta' => 'sometimes|digits:16',
                'compania'       => 'sometimes|string|min:3|max:100',
            ]);
        }

        unset($datosUsuario['foto']);

        $usuarioActualizado = $this->userService->actualizarPerfil(
            $usuario->id,
            $datosUsuario, 
            $request->file('foto'),
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
}
