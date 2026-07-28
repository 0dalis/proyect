<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LandingController extends Controller
{
    public function index()
    {
        $planes = Plan::where('public', true)->orderBy('precio')->get();

        return view('landing', compact('planes'));
    }

    public function contacto(Request $request)
    {
        $validated = $request->validate([
            'nombre'    => 'required|string|max:255',
            'empresa'   => 'required|string|max:255',
            'email'     => 'required|email|max:255',
            'telefono'  => 'nullable|string|max:30',
            'mensaje'   => 'required|string|max:2000',
            'plan_interes' => 'nullable|string|max:100',
        ]);

        // Guardar en contactos
        DB::table('contactos')->insert([
            'nombre'       => $validated['nombre'],
            'empresa'      => $validated['empresa'],
            'email'        => $validated['email'],
            'telefono'     => $validated['telefono'] ?? null,
            'mensaje'      => $validated['mensaje'],
            'plan_interes' => $validated['plan_interes'] ?? null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gracias por contactarnos. Te responderemos en menos de 24 horas.',
        ]);
    }

    public function registro(Request $request)
    {
        $validated = $request->validate([
            'nombre_empresa' => 'required|string|max:255',
            'nombre'         => 'required|string|max:255',
            'apellido'       => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:8|confirmed',
            'telefono'       => 'nullable|string|max:30',
            'plan_id'        => 'nullable|exists:plans,id',
            'terminos'       => 'required|accepted',
        ]);

        DB::transaction(function () use ($validated) {
            $company = Company::create([
                'name'              => $validated['nombre_empresa'],
                'code'              => Str::upper(Str::random(8)),
                'slug'              => Str::slug($validated['nombre_empresa'] . '-' . Str::random(4)),
                'plan_id'           => $validated['plan_id'] ?? null,
                'trial_ends_at'     => now()->addDays(14),
                'is_active'         => true,
            ]);

            $user = User::create([
                'company_id' => $company->id,
                'email'      => $validated['email'],
                'password'   => $validated['password'],
                'is_active'  => true,
            ]);

            $user->employee()->create([
                'company_id'    => $company->id,
                'first_name'    => $validated['nombre'],
                'last_name'     => $validated['apellido'],
                'employee_code' => 'OWNER-' . $company->id,
                'pin'           => '0000',
                'is_active'     => true,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Tu empresa ha sido registrada exitosamente. Revisa tu correo para activar tu cuenta.',
        ]);
    }

    public function acceso()
    {
        $planes = Plan::where('public', true)->orderBy('precio')->get();

        return view('acceso', compact('planes'));
    }

    public function privacidad()
    {
        return view('legal.privacidad');
    }

    public function terminos()
    {
        return view('legal.terminos');
    }
}
