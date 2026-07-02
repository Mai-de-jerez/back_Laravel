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
        return (new UserProfileResource($usuario))->response();
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
            'email' => 'sometimes|email|unique:usuarios,email,' . $usuario->id,
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

        // Pasamos al servicio solo lo que le corresponde
        $usuarioActualizado = $this->userService->actualizarPerfil(
        $usuario->id,
        $request->except(['foto', 'password_confirmation', '_token']), 
        $request->file('foto'),
        $datosRelacion
    );

        $usuarioActualizado->load(['medico', 'paciente']);
        return (new UserProfileResource($usuarioActualizado))->response();
    }

    
}
