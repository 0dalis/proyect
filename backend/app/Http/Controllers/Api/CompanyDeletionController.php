<?php

namespace App\Http\Controllers\Api;

use App\Actions\RequestCompanyDeletion;
use App\Http\Controllers\Controller;
use App\Models\CompanyDeletion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CompanyDeletionController extends Controller
{
    /**
     * "Eliminar perfil de empresa de AsistControl". Solo el dueño. Pide su
     * contraseña, el motivo y escribir "eliminar datos de {empresa}".
     */
    public function store(Request $request, RequestCompanyDeletion $requestDeletion): JsonResponse
    {
        $owner = $request->user();
        $company = $owner->company;
        $phrase = self::confirmationPhrase($company->name);

        $data = $request->validate([
            'password' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:20', 'max:2000'],
            'confirmation' => ['required', 'string'],
        ], [
            'reason.min' => 'Cuéntanos el motivo con al menos 20 caracteres.',
        ]);

        if (! Hash::check($data['password'], $owner->password)) {
            throw ValidationException::withMessages(['password' => 'La contraseña no es correcta.']);
        }

        if (Str::lower(trim(preg_replace('/\s+/', ' ', $data['confirmation']))) !== Str::lower($phrase)) {
            throw ValidationException::withMessages(['confirmation' => "Escribe exactamente: {$phrase}"]);
        }

        $deletion = $requestDeletion->handle($company, $owner, trim($data['reason']));

        // Cierra también esta sesión (los tokens de la app ya se borraron)
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Recibimos tu solicitud. Te enviamos un correo con el enlace para descargar tus datos.',
            'purge_after' => $deletion->purge_after,
        ], 202);
    }

    /**
     * Enlace del correo (48 horas). Público: el acceso de la empresa ya está cerrado.
     */
    public function download(string $token): BinaryFileResponse|RedirectResponse
    {
        $deletion = CompanyDeletion::findByExportToken($token);
        $frontend = rtrim(config('app.frontend_url'), '/');

        if (! $deletion || ! $deletion->exportLinkIsValid() || ! $deletion->export_path
            || ! Storage::disk('local')->exists($deletion->export_path)) {
            return redirect("{$frontend}/login?export=expired");
        }

        $deletion->forceFill(['export_downloaded_at' => now()])->save();

        return response()->download(
            Storage::disk('local')->path($deletion->export_path),
            'asistcontrol-'.Str::slug($deletion->company_name).'-datos.zip',
        );
    }

    public static function confirmationPhrase(string $companyName): string
    {
        return 'eliminar datos de '.trim(preg_replace('/\s+/', ' ', $companyName));
    }
}
