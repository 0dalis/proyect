<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Imágenes en el disco privado (storage/app). La base de datos guarda solo la
 * ruta relativa: {carpeta}/{empresa}/{uuid}.{ext}. Los archivos de una empresa
 * viven juntos para poder borrarlos de un golpe al eliminar la cuenta.
 */
class StoredImage
{
    /** Carpetas permitidas. Todo lo demás se ignora al borrar o servir. */
    private const FOLDERS = ['avatars', 'logos', 'photos', 'attendance', 'exports'];

    /** @var array<string, string> */
    private const MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
    ];

    /**
     * Guarda la imagen nueva en disco y borra la anterior. Devuelve la ruta
     * relativa para la base de datos.
     *
     * @param  string  $folder  avatars | logos | photos
     */
    public function store(UploadedFile $file, int $companyId, string $folder, ?string $current = null): string
    {
        $this->delete($current);

        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->extension());
        $path = sprintf('%s/%d/%s.%s', $folder, $companyId, Str::uuid(), $extension);

        Storage::disk('local')->put($path, (string) file_get_contents($file->getRealPath()));

        return $path;
    }

    /**
     * Borra un archivo solo si la ruta parece una de las nuestras (nunca
     * rutas arbitrarias ni rutas hacia fuera del disco).
     */
    public function delete(?string $path): void
    {
        if (! $this->isSafe($path)) {
            return;
        }

        Storage::disk('local')->delete($path);
    }

    /**
     * Ruta absoluta del archivo en disco: la usa dompdf, que no lee del disco
     * privado por HTTP.
     */
    public function absolutePath(?string $path): ?string
    {
        if (! $this->isSafe($path)) {
            return null;
        }

        return Storage::disk('local')->path($path);
    }

    /**
     * Respuesta para el navegador: bytes + tipo correcto y caché privada.
     * El cliente agrega ?v={uuid} al subir, así el navegador pide el nuevo.
     */
    public function response(?string $path): ?Response
    {
        if (! $this->isSafe($path)) {
            return null;
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            return null;
        }

        return response($disk->get($path), 200, [
            'Content-Type' => self::MIME_TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'application/octet-stream',
            'Content-Disposition' => 'inline',
            // Private: no lo guardamos en cachés compartidas
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }

    /**
     * URL relativa de la ruta, con ?v={uuid} para invalidar la caché del
     * navegador cuando cambia la imagen. Devuelve null si no hay imagen.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function url(string $routeName, ?string $path, array $parameters = []): ?string
    {
        if (! $path) {
            return null;
        }

        return route($routeName, [...$parameters, 'v' => basename($path)], false);
    }

    private function isSafe(?string $path): bool
    {
        if (! $path || $path === '' || str_contains($path, '..')) {
            return false;
        }

        return in_array(explode('/', $path)[0], self::FOLDERS, true);
    }
}
