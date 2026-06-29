<?php

namespace App\Services;

use App\Exceptions\Files\FileUploadException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $this->validarArchivo($file); 

        $nombre = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        $ruta = $file->storeAs($carpeta, $nombre, 'public');

        if (!$ruta) {
            Log::error('Error al subir foto al disco público', [
                'nombre_original' => $file->getClientOriginalName()
            ]);
            throw new FileUploadException('Error al subir la imagen');
        }

        Log::info('Foto subida exitosamente', ['ruta' => $ruta]);

        return $ruta;
    }

    /**
     *  Validar archivo
     */
    private function validarArchivo(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new FileUploadException('El archivo no es válido');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, self::FORMATOS_PERMITIDOS)) {
            throw new FileUploadException(
                'Formato no permitido. Usa: ' . implode(', ', self::FORMATOS_PERMITIDOS)
            );
        }

        if ($file->getSize() > self::TAMANO_MAXIMO_KB * 1024) {
            throw new FileUploadException('La imagen no puede superar los ' . self::TAMANO_MAXIMO_KB . 'KB');
        }

        if (!getimagesize($file->getRealPath())) {
            throw new FileUploadException('El archivo no es una imagen válida');
        }
    }

    public function eliminarFoto(string $ruta): void
    {
        if (empty($ruta) || $ruta === $this->getFotoDefault()) {
            return;
        }

        if (!Storage::disk('public')->exists($ruta)) {
            Log::warning("Intento de eliminar foto que no existe", ['ruta' => $ruta]);
            return;
        }

        if (!Storage::disk('public')->delete($ruta)) {
            Log::error("No se pudo eliminar la foto", ['ruta' => $ruta]);
            throw new FileUploadException('Error al eliminar la foto');
        }

        Log::info('Foto eliminada exitosamente', ['ruta' => $ruta]);
    }

    public function obtenerUrl(string $ruta): string
    {
        if (empty($ruta)) {
            return $this->obtenerUrl($this->getFotoDefault());
        }

        return Storage::disk('public')->url($ruta);
    }

    /**
     * Actualizar foto de un usuario
     */
    public function actualizarFoto(UploadedFile $file, ?string $rutaAnterior = null, string $carpeta = 'fotos'): string
    {
        // Subir nueva foto
        $nuevaRuta = $this->subirFoto($file, $carpeta);
        
        // Eliminar foto anterior (si existe y no es la default)
        if ($rutaAnterior && $rutaAnterior !== $this->getFotoDefault()) {
            $this->eliminarFoto($rutaAnterior);
        }
        
        return $nuevaRuta;
    }
}


   
