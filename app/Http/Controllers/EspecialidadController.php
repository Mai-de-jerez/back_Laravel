<?php

namespace App\Http\Controllers;

use App\Services\EspecialidadService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\EspecialidadResource;

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

}