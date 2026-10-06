<?php

namespace App\Http\Controllers;

use App\Services\CitaService;
use App\Models\Cita;
use App\Models\Medico;
use App\Http\Requests\CrearCitaRequest;
use App\Http\Requests\ActualizarCitaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\CitaResource;
use Illuminate\Support\Facades\Log;

class CitaController extends Controller
{
    public function __construct(private CitaService $citaService) {}

    /**
     * Listar citas con filtros opcionales y paginación
     * @param Request $request
     * @return JsonResponse
     */
    public function listarCitas(Request $request): JsonResponse
    {
        $filtros = $request->only(['id', 'id_medico', 'id_paciente', 'estado', 'fecha']);

        $resultado = $this->citaService->obtenerTodasLasCitas($filtros);

        return response()->json([
            'citas' => CitaResource::collection($resultado['citas']),
            'pagina_actual' => $resultado['pagina_actual'],
            'ultima_pagina' => $resultado['ultima_pagina'],
            'por_pagina' => $resultado['por_pagina'],
            'total' => $resultado['total'],
        ], 200);
    }

    /**
     * Ver huecos disponibles de un médico en una fecha concreta
     */
    public function citasPorMedico(Medico $medico): JsonResponse
    {
        $resultado = $this->citaService->obtenerProximoDiaConHuecos($medico->id);

        return response()->json([
            'medico' => [
                'id' => $medico->id,
                'nombre_completo' => $medico->usuario->nombre_completo ?? 'Médico',
            ],
            'fecha' => $resultado['fecha'],
            'huecos_disponibles' => $resultado['huecos'],
        ], 200);
    }
    

    /**
     * Admin: Mostrar detalle de una cita
     */
    public function mostrarCita(Cita $cita): JsonResponse
    {
        return response()->json([
            'cita' => new CitaResource($cita)
        ], 200);
    }

    /**
     * Crear una cita 
     */
    public function crearCita(CrearCitaRequest $request): JsonResponse
    {
        $cita = $this->citaService->crearCita($request->validated());

        Log::info('Cita creada vía admin/controller', [
            'admin_id' => $request->user()->id,
            'cita_id' => $cita->id,
            'id_medico' => $cita->id_medico,
            'id_paciente' => $cita->id_paciente,
        ]);

        return response()->json([
            'mensaje' => 'Cita creada correctamente',
            'cita' => new CitaResource($cita),
        ], 201);
    }

    /**
     * Actualizar una cita
     */
    public function actualizarCita(ActualizarCitaRequest $request, Cita $cita): JsonResponse
    {
        $datosCita = $request->validated();
        $citaActualizada = $this->citaService->actualizarCita($cita, $datosCita);

        Log::info('Cita actualizada por admin', [
            'admin_id' => $request->user()->id,
            'cita_actualizada_id' => $cita->id,
            'campos_actualizados' => array_keys($datosCita),
        ]);

        return response()->json([
            'mensaje' => 'Cita actualizada correctamente',
            'cita' => new CitaResource($citaActualizada),
        ], 200);
    }

    /**
     * Endpoint único para obtener las citas del usuario logueado (Médico o Paciente)
     */
    public function misCitas(Request $request): JsonResponse
    {
        $resultado = $this->citaService->obtenerMisCitas(
            $request->user(),
            $request->only(['fecha', 'estado', 'por_pagina'])
        );

        return response()->json([
            'citas'          => CitaResource::collection($resultado['citas']),
            'pagina_actual'  => $resultado['pagina_actual'],
            'ultima_pagina'  => $resultado['ultima_pagina'],
            'por_pagina'     => $resultado['por_pagina'],
            'total'          => $resultado['total'],
        ], 200);
    }

}