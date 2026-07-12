<?php

namespace App\Http\Controllers;

use App\Services\HorarioService;
use App\Enums\DiaSemana;
use App\Http\Resources\HorarioResource;
use App\Http\Requests\StoreHorarioRequest;
use App\Http\Requests\UpdateHorarioRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

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

    public function listarTodos(): JsonResponse
    {
        $horarios = $this->horarioService->obtenerTodosLosHorarios();

        return response()->json([
            'horarios' => HorarioResource::collection($horarios)
        ], 200);
    }

    /**
     * Admin: ver detalle de un horario
     */

    public function mostrar(int $id): JsonResponse
    {
        $horario = $this->horarioService->obtenerHorarioPorId($id);

        if (!$horario) {
            return response()->json([
                'mensaje' => 'Horario no encontrado'
            ], 404);
        }

        return response()->json([
            'horario' => new HorarioResource($horario)
        ], 200);
    }

    /**
     * Crear un horario
     */
    public function crearHorario(StoreHorarioRequest $request): JsonResponse  
    {
        try {
            $horario = $this->horarioService->crearHorario(
                $request->id_medico,
                $request->validated() 
            );
 
            return response()->json([
                'mensaje' => 'Horario creado correctamente',
                'horario' => new HorarioResource($horario),
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }
    }

    /**
     * Actualizar un horario (solo admin)
     */
    public function update(UpdateHorarioRequest $request, int $id): JsonResponse 
    {
        try {
            $horario = $this->horarioService->actualizarHorario(
                $id,
                $request->validated() 
            );

            return response()->json([
                'mensaje' => 'Horario actualizado correctamente',
                'horario' => new HorarioResource($horario),
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }
    }

    /**
     * Eliminar un horario
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $this->horarioService->eliminarHorario(
                $id,
                $request->user()->medico->id 
            );

            return response()->json([
                'mensaje' => 'Horario eliminado correctamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }
    }
}
