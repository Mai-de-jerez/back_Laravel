<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EspecialidadService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\EspecialidadResource;

class EspecialidadController extends Controller
{
    public function __construct(
        private EspecialidadService $especialidadService
    ) {}

    public function index(): JsonResponse
    {
        $especialidades = $this->especialidadService->listar();
        
        return response()->json(
            EspecialidadResource::collection($especialidades)
        );
    }
}