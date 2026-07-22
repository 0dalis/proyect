<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use App\Models\Plan;


class CompanyController extends Controller implements HasMiddleware{
    public static function middleware(): array
    {
        return ['auth'];
    }

    // Mostrar todas las empresas
    public function index(){
        $companies = Company::with('plan')->get();
        $planes = Plan::all();
        return view('system.companies', compact('companies', 'planes'));
    }
    public function store(Request $request){
        $request->merge([
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'code'                 => 'required|string|max:50|unique:companies,code',
            'plan_id'              => 'required|exists:plans,id',
            'trial_ends_at'        => 'nullable|date',
            'subscription_ends_at' => 'nullable|date',
            'is_active'            => 'required|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Company::create($validated);

        return response()->json(['success' => true, 'message' => 'Empresa creada correctamente']);
    }

    public function update(Request $request){
        $request->merge([
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        $company = Company::findOrFail($request->company_id);

        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'code'                 => 'required|string|max:50|unique:companies,code,' . $company->id,
            'plan_id'              => 'required|exists:plans,id',
            'trial_ends_at'        => 'nullable|date',
            'subscription_ends_at' => 'nullable|date',
            'is_active'            => 'required|boolean',
        ]);

        if ($company->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $company->update($validated);

        return response()->json(['success' => true, 'message' => 'Empresa actualizada correctamente']);
    }
}
