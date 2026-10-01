<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    private function getCompany(Request $request): Company
    {
        return $request->user()->company;
    }

    public function index(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $query = AuditLog::where('company_id', $company->id)
            ->with('user:id,email')
            ->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        $perPage = min((int) $request->input('per_page', 50), 200);

        return response()->json($query->paginate($perPage));
    }
}
