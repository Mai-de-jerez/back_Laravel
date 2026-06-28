<?php

namespace App\Services;

use App\Exceptions\Files\FileUploadException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    private const FORMATOS_PERMITIDOS = ['jpeg', 'jpg', 'png', 'webp'];
    private const TAMANO_MAXIMO_KB = 2048;

    public function getFotoDefault(): string
    {
        return config('app.foto_default', 'fotos/default.png');
    }

    public function subirFoto(UploadedFile $file, string $carpeta = 'fotos'): string
    {
        if (!in_array($file->getClientOriginalExtension(), self::FORMATOS_PERMITIDOS)) {
            throw new FileUploadException('Formato no permitido. Usa jpeg, jpg, png o webp');
        }

        if ($file->getSize() > self::TAMANO_MAXIMO_KB * 1024) {
            throw new FileUploadException('La imagen no puede superar los 2MB');
        }

        $ruta = $file->store($carpeta, 'public');

        if (!$ruta) {
            Log::error('Error al subir foto al disco público');
            throw new FileUploadException();
        }

        return $ruta;
    }

    public function eliminarFoto(string $ruta): void
    {
        if ($ruta === $this->getFotoDefault()) {
            return;
        }

        if (!Storage::disk('public')->exists($ruta)) {
            Log::warning("Intento de eliminar foto que no existe: {$ruta}");
            return;
        }

        if (!Storage::disk('public')->delete($ruta)) {
            Log::error("No se pudo eliminar la foto: {$ruta}");
        }
    }

    public function obtenerUrl(string $ruta): string
    {
        return Storage::disk('public')->url($ruta);
    }
}


   
