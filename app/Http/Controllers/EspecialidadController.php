<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EspecialidadService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\EspecialidadResource;
use App\Models\Especialidad;

class EspecialidadController extends Controller
{
    public function __construct(
        private EspecialidadService $especialidadService
    ) {}

    /**
     * Listar especialidades
     */
    public function listarEspecialidades(): JsonResponse
    {
        $especialidades = $this->especialidadService->listar();

        return response()->json([
            'especialidades' => EspecialidadResource::collection($especialidades)
        ], 200);
    }

    /**
     * Listar medicos por especialidad
     */
    public function listarMedicosPorEspecialidad(Especialidad $especialidad): JsonResponse
    {
        $resultado = $this->especialidadService->listarMedicosPorEspecialidad($especialidad);

        return response()->json($resultado, 200);
    }
}