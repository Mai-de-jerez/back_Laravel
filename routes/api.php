<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\HorarioController;


// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/recuperar-password', [AuthController::class, 'recuperarPassword']);
Route::post('/restablecer-password', [AuthController::class, 'restablecerPassword']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/mi-perfil', [UserController::class, 'perfil']);
    Route::post('/actualizar-perfil', [UserController::class, 'actualizar']);
    Route::get('/especialidades', [EspecialidadController::class, 'index']);
    Route::get('/mis-horarios', [HorarioController::class, 'misHorarios']);

    Route::middleware(['admin'])->prefix('admin')->group(function () {
        Route::get('/usuarios', [UserController::class, 'listarUsuarios']);
        Route::get('/usuarios/{id}', [UserController::class, 'mostrarUsuario']);
        Route::post('/usuarios', [UserController::class, 'crearUsuario']);
        Route::post('/usuarios/{id}', [UserController::class, 'actualizarUsuario']);
        Route::get('/horarios', [HorarioController::class, 'listarTodos']);
        Route::get('/horarios/{id}', [HorarioController::class, 'mostrar']);
        Route::post('/horarios', [HorarioController::class, 'crearHorario']);
        Route::put('/horarios/{id}', [HorarioController::class, 'update']);
        Route::delete('/horarios/{id}', [HorarioController::class, 'destroy']);
    });
});



