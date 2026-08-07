<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Rules\Recaptcha;
use Illuminate\Support\Facades\Mail;
use App\Mail\ActivarCuentaMail;

class LandingController extends Controller{
    public function index(){
        $planes = Plan::where('public', true)->orderBy('precio')->get();
        $daysTrial = config('app.days_trial');

        return view('landing', compact('planes', 'daysTrial'));
    }

    public function contacto(Request $request){
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

    public function registro(Request $request){
        // 1. VALIDACIÓN HONEYPOT (Detección de Bots)
        // Si el campo oculto 'plan' viene lleno, es un bot.
        if ($request->filled('plan')) {
            // Retornamos éxito simulado para engañar al bot y no darle pistas
            return response()->json([
                'success' => true,
                'message' => 'Tu empresa ha sido registrada exitosamente. Revisa tu correo.',
            ], 200);
        }

        // 2. Validación normal de datos
        $validated = $request->validate([
            'nombre_empresa' => 'required|string|max:255',
            'nombre'         => 'required|string|max:255',
            'apellido'       => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:8',
            'terminos'       => 'required|accepted',
            'recaptcha_token' => ['required', new Recaptcha()],
        ]);

        $daysTrial = (int) env('DAYS_TRIAL');

        DB::transaction(function () use ($validated, $daysTrial) {
            $planPremium = Plan::find(3);

            // A) Empresa inactiva
            $company = Company::create([
                'name'          => $validated['nombre_empresa'],
                'code'          => Str::upper(Str::random(8)),
                'slug'          => Str::slug($validated['nombre_empresa'] . '-' . Str::random(4)),
                'plan_id'       => $planPremium?->id,
                'trial_ends_at' => now()->addDays($daysTrial),
                'is_active'     => false,
            ]);

            // B) Token de 24 horas para el correo
            $token = Str::random(64);

            // C) Usuario inactivo
            $user = User::create([
                'company_id'                  => $company->id,
                'email'                       => $validated['email'],
                'password'                    => $validated['password'],
                'is_active'                   => false,
                'activation_token'            => $token,
                'activation_token_expires_at' => now()->addHours(24),
            ]);

            // D) Asignar roles: owner (2) + admin (4)
            if (method_exists($user, 'assignRole')) {
                $user->assignRole([2, 4]);
            }

            // E) Empleado dueño
            $user->employee()->create([
                'company_id'    => $company->id,
                'first_name'    => $validated['nombre'],
                'last_name'     => $validated['apellido'],
                'employee_code' => 'OWNER-' . $company->id,
                'pin'           => '0000',
                'is_active'     => false,
            ]);

            // F) Correo con link de activación
            $nombreCompleto = $validated['nombre'] . ' ' . $validated['apellido'];
            Mail::to($user->email)->send(new ActivarCuentaMail($user, $nombreCompleto, $validated['nombre_empresa']));
        });

        return response()->json([
            'success' => true,
            'message' => 'Te hemos enviado un correo de confirmación. Tendrás 24 horas para activar tu cuenta.',
        ]);
    }

    public function acceso(){
        $daysTrial = config('app.days_trial');

        return view('acceso', compact('daysTrial'));
    }

    public function privacidad(){
        return view('legal.privacidad');
    }

    public function terminos(){
        return view('legal.terminos');
    }

    public function sistema(){
        $daysTrial = config('app.days_trial');
        $planes = Plan::where('public', true)->orderBy('precio')->get();

        return view('sistema-detalle', compact('daysTrial', 'planes'));
    }

    public function planesDetalle(){
        $daysTrial = config('app.days_trial');
        $planes = Plan::where('public', true)->orderBy('precio')->get();

        return view('planes-detalle', compact('daysTrial', 'planes'));
    }

    public function activarCuenta(Request $request, $id){
        if (! $request->hasValidSignature()) {
            return redirect()->route('landing');
        }

        $user = User::with('employee', 'company')->find($id);

        if (! $user || ! $user->activation_token) {
            return redirect()->route('landing');
        }

        if ($user->activation_token_expires_at && now()->gt($user->activation_token_expires_at)) {
            return redirect()->route('landing');
        }

        if ($user->email_verified_at && $user->is_active) {
            return redirect()->route('login')->with('success', 'Tu cuenta ya ha sido activada. Inicia sesión para comenzar.');
        }

        $nombreCompleto  = ($user->employee?->first_name ?? '') . ' ' . ($user->employee?->last_name ?? '');
        $nombreEmpresa   = $user->company?->name ?? '';
        $daysTrial       = config('app.days_trial');
        $activationToken = $user->activation_token;

        return view('activar-perfil', compact('user', 'nombreCompleto', 'nombreEmpresa', 'daysTrial', 'activationToken'));
    }

    public function verificarCuenta(Request $request, $id){
        $request->validate([
            'recaptcha_token'  => ['required', new Recaptcha()],
            'activation_token' => 'required|string|size:64',
        ]);

        $user = User::with('employee', 'company')->findOrFail($id);

        if (! $user->activation_token || $request->activation_token !== $user->activation_token) {
            return response()->json([
                'success' => false,
                'message' => 'Token de activación inválido. Utiliza el enlace enviado a tu correo.',
            ]);
        }

        if ($user->email_verified_at && $user->is_active) {
            return response()->json([
                'success'  => true,
                'message'  => 'Tu cuenta ya ha sido activada previamente.',
                'redirect' => route('login'),
            ]);
        }

        if ($user->activation_token_expires_at && now()->gt($user->activation_token_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'El periodo de activación ha expirado. Contacta a soporte para recibir asistencia.',
            ]);
        }

        DB::transaction(function () use ($user) {
            $user->update([
                'email_verified_at'           => now(),
                'is_active'                   => true,
                'activation_token'            => null,
                'activation_token_expires_at' => null,
            ]);

            $user->company()->update(['is_active' => true]);

            if ($user->employee) {
                $user->employee()->update(['is_active' => true]);
            }
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Perfil verificado exitosamente. Redirigiendo al inicio de sesión...',
            'redirect' => route('login'),
        ]);
    }
}
