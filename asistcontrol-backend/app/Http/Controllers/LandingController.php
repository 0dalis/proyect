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
            $planPremium = Plan::where('slug', 'premium')->first();

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

            // D) Rol de dueño
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('owner');
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
            Mail::to($user->email)->queue(new ActivarCuentaMail($user));
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
    public function activarCuenta(Request $request, $id){
        // Validar que la firma sea válida y no haya expirado (24h)
        if (! $request->hasValidSignature()) {
            abort(401, 'El enlace de activación es inválido o ha expirado.');
        }
        $user = User::findOrFail($id);
        // Si la cuenta aún no ha sido verificada
        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
            // $user->status = 'activo'; // Si manejas un campo de estado personalizado
            $user->save();
        }
        // Redirigir con mensaje de éxito (puedes ajustar la ruta según tu app)
        return redirect()->route('login')->with('success', '¡Tu cuenta ha sido activada correctamente! Ya puedes iniciar sesión.');
    }
}
