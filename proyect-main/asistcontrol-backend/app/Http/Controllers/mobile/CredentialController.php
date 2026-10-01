<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\EmployeeCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CredentialController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['available' => false, 'message' => 'Sin empleado asociado.'], 404);
        }

        $credential = EmployeeCredential::where('employee_id', $employee->id)->first();

        if (! $credential || ! $credential->download_enabled || ! $credential->pdf_path) {
            return response()->json([
                'available' => false,
                'message' => 'Tu credencial aún no está disponible para descarga.',
            ]);
        }

        return response()->json([
            'available' => true,
            'orientation' => $credential->orientation,
            'issued_at' => $credential->issued_at,
            'pdf_url' => $credential->pdf_url,
        ]);
    }

    public function pdf(Request $request)
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['message' => 'Sin empleado asociado.'], 404);
        }

        $credential = EmployeeCredential::where('employee_id', $employee->id)->first();

        if (! $credential || ! $credential->download_enabled || ! $credential->pdf_path) {
            return response()->json(['message' => 'Credencial no disponible.'], 403);
        }

        if (! Storage::disk('public')->exists($credential->pdf_path)) {
            return response()->json(['message' => 'Archivo no encontrado.'], 404);
        }

        return Storage::disk('public')->download(
            $credential->pdf_path,
            'credencial_' . $employee->employee_code . '.pdf'
        );
    }
}
