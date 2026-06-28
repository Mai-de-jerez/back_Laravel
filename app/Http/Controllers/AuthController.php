<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;           

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'nombre'    => 'required|string|max:100',
            'apellidos' => 'required|string|max:150',
            'email'     => 'required|email|unique:usuarios,email',
            'password'  => 'required|string|min:8|confirmed',
            'telefono'  => 'nullable|string|max:20',
            'foto' => 'nullable|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $resultado = $this->authService->register(
            $request->except('foto'),
            $request->file('foto')
        );

        return response()->json($resultado, 201);
    }


    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $resultado = $this->authService->login($request->all());

        return response()->json($resultado);
    }


    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(['mensaje' => 'Sesión cerrada correctamente']);
    }


    public function recuperarPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:usuarios,email',
        ]);

        $resultado = $this->authService->recuperarPassword($request->email);

        return response()->json($resultado);
    }


    public function restablecerPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $this->authService->restablecerPassword($request->all());

        return response()->json(['mensaje' => 'Contraseña restablecida correctamente']);
    }
}