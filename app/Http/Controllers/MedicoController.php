<?php

namespace App\Http\Controllers;

use App\Services\MedicoService;
use App\Http\Resources\MedicoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicoController extends Controller
{
    public function __construct(
        private MedicoService $medicoService
    ) {}

    /**
     * Listar médicos filtrados opcionalmente por especialidad y/o centro.
     */
    public function listarMedicos(Request $request): JsonResponse
    {
        $request->validate([
            'id_especialidad' => 'nullable|integer|exists:especialidades,id',
            'id_centro'       => 'nullable|integer|exists:centros,id',
        ]);

        $filtros = $request->only(['id_especialidad', 'id_centro']);

        $medicos = $this->medicoService->listarMedicos($filtros);

        return response()->json([
            'medicos' => MedicoResource::collection($medicos),
        ], 200);
    }
}