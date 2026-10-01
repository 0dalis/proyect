<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CredentialController extends Controller
{
    private function getCompany(Request $request): Company
    {
        return $request->user()->company;
    }

    private function credentialFor(Company $company, Employee $employee): EmployeeCredential
    {
        $credential = EmployeeCredential::firstOrCreate(
            ['employee_id' => $employee->id],
            [
                'company_id' => $company->id,
                'user_id' => $employee->user_id,
                'orientation' => 'horizontal',
                'download_enabled' => false,
            ]
        );

        if (! $credential->qr_token) {
            $credential->qr_token = Str::random(40);
            $credential->save();
        }

        $credential->qr_payload = EmployeeCredential::buildPayload($company, $employee, $credential->qr_token);
        $credential->save();

        return $credential;
    }

    public function show(Request $request, $employeeId): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->with(['office:id,name', 'area:id,name'])->findOrFail($employeeId);

        $credential = $this->credentialFor($company, $employee);

        return response()->json([
            'credential' => $credential,
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'full_name' => $employee->full_name,
                'position' => $employee->position,
                'user_id' => $employee->user_id,
                'office' => $employee->office?->name,
                'area' => $employee->area?->name,
            ],
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'logo_path' => $company->logo_path,
            ],
        ]);
    }

    public function update(Request $request, $employeeId): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($employeeId);

        $validator = Validator::make($request->all(), [
            'orientation' => 'nullable|in:horizontal,vertical',
            'design' => 'nullable|array',
            'download_enabled' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credential = $this->credentialFor($company, $employee);
        $credential->update($request->only(['orientation', 'design', 'download_enabled']));

        return response()->json(['message' => 'Credencial actualizada.', 'credential' => $credential]);
    }

    public function regenerateQr(Request $request, $employeeId): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($employeeId);

        $credential = $this->credentialFor($company, $employee);
        $credential->qr_token = Str::random(40);
        $credential->qr_payload = EmployeeCredential::buildPayload($company, $employee, $credential->qr_token);
        $credential->save();

        return response()->json(['message' => 'Código QR regenerado.', 'credential' => $credential]);
    }

    public function uploadPdf(Request $request, $employeeId): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($employeeId);

        $validator = Validator::make($request->all(), [
            'pdf' => 'required|file|mimes:pdf|max:10240',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credential = $this->credentialFor($company, $employee);

        if ($credential->pdf_path) {
            Storage::disk('public')->delete($credential->pdf_path);
        }

        $path = $request->file('pdf')->store("credentials/{$company->id}", 'public');

        $credential->update([
            'pdf_path' => $path,
            'issued_at' => $credential->issued_at ?? now(),
            'last_printed_at' => now(),
            'print_count' => $credential->print_count + 1,
        ]);

        return response()->json([
            'message' => 'Credencial guardada.',
            'credential' => $credential->fresh(),
        ]);
    }

    public function downloadPdf(Request $request, $employeeId)
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($employeeId);
        $credential = $this->credentialFor($company, $employee);

        if (! $credential->pdf_path || ! Storage::disk('public')->exists($credential->pdf_path)) {
            return response()->json(['message' => 'No hay credencial generada.'], 404);
        }

        return Storage::disk('public')->download(
            $credential->pdf_path,
            'credencial_' . $employee->employee_code . '.pdf'
        );
    }

    public function uploadPhoto(Request $request, $employeeId): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($employeeId);

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|max:4096',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credential = $this->credentialFor($company, $employee);

        if ($credential->photo_path) {
            Storage::disk('public')->delete($credential->photo_path);
        }

        $path = $request->file('photo')->store("employees/photos", 'public');
        $credential->update(['photo_path' => $path]);

        return response()->json(['message' => 'Foto actualizada.', 'credential' => $credential->fresh()]);
    }

    public function removePhoto(Request $request, $employeeId): JsonResponse
    {
        $company = $this->getCompany($request);
        $employee = $company->employees()->findOrFail($employeeId);
        $credential = $this->credentialFor($company, $employee);

        if ($credential->photo_path) {
            Storage::disk('public')->delete($credential->photo_path);
            $credential->update(['photo_path' => null]);
        }

        return response()->json(['message' => 'Foto eliminada.']);
    }
}
