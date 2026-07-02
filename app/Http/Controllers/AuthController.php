<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;  
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'nombre'         => 'required|string|min:3|max:100|regex:/^[\pL\s]+$/u',
            'apellidos'      => 'required|string|min:3|max:150|regex:/^[\pL\s]+$/u',
            'email'          => 'required|email|unique:usuarios,email',
            'password'       => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'telefono'       => 'nullable|string|max:20',
            'foto'           => 'nullable|file', 
            'numero_tarjeta' => 'required|digits:16',
            'compania'       => 'required|string|min:3|max:100',
        ]);

        // Las excepciones de subida se manejan solas en bootstrap/app.php
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
            'email' => 'required|email',
        ]);

        // Si el email no existe, EmailNotFoundException se encarga de mentir con un 200 en bootstrap/app.php
        $resultado = $this->authService->recuperarPassword($request->email);
        
        return response()->json($resultado);
    }


    public function restablecerPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $this->authService->restablecerPassword($request->all());

        return response()->json(['mensaje' => 'Contraseña restablecida correctamente']);
    }
}