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

    
}
