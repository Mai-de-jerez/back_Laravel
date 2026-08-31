<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Services\HorarioService;
use App\Http\Resources\HorarioResource;
use App\Http\Requests\CrearHorarioRequest;
use App\Http\Requests\ActualizarHorarioRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class HorarioController extends Controller
{
    public function __construct(private HorarioService $horarioService) {}

    /**
     * Médico: ver sus propios horarios
     */

    public function misHorarios(Request $request): JsonResponse
    {
        $usuario = $request->user();

        if (!$usuario->esMedico()) {
            return response()->json([
                'mensaje' => 'Solo los médicos pueden ver sus horarios'
            ], 403);
        }

        $medicoId = $usuario->medico->id;

        if (!$medicoId) {
            return response()->json([
                'mensaje' => 'Médico no encontrado para este usuario'
            ], 404);
        }

        $horarios = $this->horarioService->obtenerHorarioMedico($medicoId);

        return response()->json([
            'horarios' => HorarioResource::collection($horarios)
        ], 200);
    }

    /**
     * Admin: ver todos los horarios
     */

    public function listarHorarios(): JsonResponse
    {
        $horarios = $this->horarioService->obtenerTodosLosHorarios();

        return response()->json([
            'horarios' => HorarioResource::collection($horarios)
        ], 200);
    }

    /**
     * Admin: ver detalle de un horario
     */
    public function mostrarHorario(Horario $horario): JsonResponse
    {
        return response()->json([
            'horario' => new HorarioResource($horario)
        ], 200);
    }

    /**
     * Crear un horario
     */
    public function crearHorario(CrearHorarioRequest $request): JsonResponse  
    {
        $horario = $this->horarioService->crearHorario(
            $request->id_medico,
            $request->validated() 
        );

        Log::info('Horario creado por admin', [
            'admin_id' => $request->user()->id,
            'horario_id' => $horario->id,
            'medico_id' => $horario->id_medico,
        ]);
        
        return response()->json([
            'mensaje' => 'Horario creado correctamente',
            'horario' => new HorarioResource($horario),
        ], 201);
    }

    /**
     * Actualizar un horario (solo admin)
     */  
    public function actualizarHorario(ActualizarHorarioRequest $request, Horario $horario): JsonResponse 
    {
        $datosHorario = $request->validated();

        $horarioActualizado = $this->horarioService->actualizarHorario(
            $horario, 
            $datosHorario
        );

        Log::info('Horario actualizado por admin', [
            'admin_id' => $request->user()->id,
            'horario_actualizado_id' => $horario->id,
            'campos_actualizados' => array_keys($datosHorario),
        ]);

        return response()->json([
            'mensaje' => 'Horario actualizado correctamente',
            'horario' => new HorarioResource($horarioActualizado),
        ], 200);
    }

    /**
     * Eliminar un horario
     */
    public function eliminarHorario(Horario $horario): JsonResponse
    {
        $this->horarioService->eliminarHorario($horario);

        return response()->json(['mensaje' => 'Horario eliminado correctamente'], 200);
    }
}
