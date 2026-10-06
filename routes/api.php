<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\CitaController;

// Rutas públicas
Route::post('/registro', [AuthController::class, 'registro']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/recuperar-password', [AuthController::class, 'recuperarPassword']);
Route::post('/restablecer-password', [AuthController::class, 'restablecerPassword']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/mi-perfil', [UserController::class, 'perfil']);
    Route::post('/actualizar-perfil', [UserController::class, 'actualizar']);
    Route::get('/especialidades', [EspecialidadController::class, 'listarEspecialidades']);
    Route::get('/especialidades/{especialidad}/medicos', [EspecialidadController::class, 'listarMedicosPorEspecialidad']);
    Route::get('/medicos/{medico}/citas', [CitaController::class, 'citasPorMedico']);
    Route::get('/mis-horarios', [HorarioController::class, 'misHorarios']);
    Route::get('/mis-citas', [CitaController::class, 'misCitas']); 

    Route::middleware(['admin'])->prefix('admin')->group(function () {
        // Usuarios
        Route::get('/usuarios', [UserController::class, 'listarUsuarios']);
        Route::post('/usuarios', [UserController::class, 'crearUsuario']);
        Route::get('/usuarios/{usuario}', [UserController::class, 'mostrarUsuario']);               
        Route::post('/usuarios/{usuario}', [UserController::class, 'actualizarUsuario']); 
        Route::patch('/usuarios/{usuario}/baja', [UserController::class, 'darDeBajaUsuario']);   
        
        // Horarios
        Route::get('/horarios', [HorarioController::class, 'listarHorarios']);
        Route::post('/horarios', [HorarioController::class, 'crearHorario']);
        Route::get('/horarios/{horario}', [HorarioController::class, 'mostrarHorario']);                    
        Route::put('/horarios/{horario}', [HorarioController::class, 'actualizarHorario']);            
        Route::delete('/horarios/{horario}', [HorarioController::class, 'eliminarHorario']);         
        
        // Citas 
        Route::get('/citas', [CitaController::class, 'listarCitas']);
        Route::post('/citas', [CitaController::class, 'crearCita']);
        Route::get('/citas/{cita}', [CitaController::class, 'mostrarCita']);                       
        Route::put('/citas/{cita}', [CitaController::class, 'actualizarCita']);                                    
    });
});

