<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Services\CentroService;


class CentroController extends Controller 
{
    public function __construct(private CentroService $centroService) {}

    /**
     * Listar todos los centros.
     */
    public function listarCentros(): JsonResponse
    {
        $centros = $this->centroService->listarCentros();

        return response()->json([
            'centros' => $centros,
        ], 200);
    }
}