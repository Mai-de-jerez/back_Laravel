<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/recuperar-password', [AuthController::class, 'recuperarPassword']);
Route::post('/restablecer-password', [AuthController::class, 'restablecerPassword']);

// Rutas protegidas
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});