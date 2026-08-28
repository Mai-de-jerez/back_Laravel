<?php

namespace App\Http\Controllers;

use App\Services\CitaService;
use App\Http\Requests\StoreCitaRequest;
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
    public function index(Request $request): JsonResponse
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

    public function store(StoreCitaRequest $request): JsonResponse
    {
        try {
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
        } catch (\InvalidArgumentException $e) {
            Log::warning('Intento fallido de crear cita', [
                'admin_id' => $request->user()->id,
                'motivo' => $e->getMessage(),
                'datos' => $request->validated(),
            ]);

            return response()->json(['mensaje' => $e->getMessage()], 422);
        }
    }
}