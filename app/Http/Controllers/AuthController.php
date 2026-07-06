<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Http\Resources\UserProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;  
use Illuminate\Validation\Rules\Password;
use App\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $foto = $request->file('foto');

        $resultado = $this->authService->register($datos, $foto);

        return response()->json([
            'mensaje' => 'Usuario registrado correctamente',
            'token' => $resultado['token'],
            'usuario' => new UserProfileResource($resultado['usuario']),
        ], 201);
    }


    public function login(Request $request): JsonResponse
    {

        $credenciales = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $resultado = $this->authService->login($credenciales);

        return response()->json([
            'mensaje' => 'Login correcto',
            'token'   => $resultado['token'],
            'usuario' => new UserProfileResource($resultado['usuario'])
        ], 200);
    }


    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(['mensaje' => 'Sesión cerrada correctamente'], 200);
    }


    public function recuperarPassword(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => 'required|email'
        ]);

        $this->authService->recuperarPassword($datos['email']);

        return response()->json(['mensaje' => 'Enlace enviado a tu email'], 200);
    }


    public function restablecerPassword(Request $request): JsonResponse
    {
        $datosValidados = $request->validate([
            'token'    => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $this->authService->restablecerPassword([
            'token'    => $datosValidados['token'],
            'password' => $datosValidados['password'],
        ]);

        return response()->json(['mensaje' => 'Contraseña restablecida correctamente'], 200);
    }


    public function refreshToken(): JsonResponse
    {
        $resultado = $this->authService->refreshToken();

        return response()->json([
            'mensaje' => 'Token refrescado correctamente',
            'token'   => $resultado['token'],
            'usuario' => new UserProfileResource($resultado['usuario'])
        ], 200);
    }
}