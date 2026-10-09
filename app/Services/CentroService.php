<?php

namespace App\Services;

use App\Models\Centro;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CentroService
{
    /**
     * Listar todos los centros con todos sus campos.
     */
    public function listarCentros(): Collection
    {
        return Centro::orderBy('nombre')->get();
    }

    /**
     * Obtener un centro por ID.
     *
     * @param int $id
     * @return Centro
     * @throws NotFoundHttpException
     */
    public function obtenerCentro(int $id): Centro
    {
        $centro = Centro::find($id);

        if (!$centro) {
            throw new NotFoundHttpException('Centro no encontrado');
        }

        return $centro;
    }
}