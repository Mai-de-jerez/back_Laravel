<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EspecialidadController;


// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/recuperar-password', [AuthController::class, 'recuperarPassword']);
Route::post('/restablecer-password', [AuthController::class, 'restablecerPassword']);
Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);

// Rutas protegidas
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/mi-perfil', [UserController::class, 'perfil']);
    Route::post('/actualizar-perfil', [UserController::class, 'actualizar']);
    Route::get('/especialidades', [EspecialidadController::class, 'index']);

    Route::middleware(['admin'])->prefix('admin')->group(function () {
        Route::get('/usuarios', [UserController::class, 'listarUsuarios']);
        Route::get('/usuarios/{id}', [UserController::class, 'mostrarUsuario']);
        Route::post('/usuarios', [UserController::class, 'crearUsuario']);
        Route::post('/usuarios/{id}', [UserController::class, 'actualizarUsuario']);
    });
});



