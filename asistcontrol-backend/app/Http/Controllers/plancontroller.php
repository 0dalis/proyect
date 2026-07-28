<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(){
        $planes = Plan::all();
        return view('system.planes', compact('planes'));
    }
    public function store(Request $request){
        $validated = $request->validate([
            'nombre'     => 'required|string|max:255',
            'tipo'       => 'nullable|string|max:100',
            'precio'     => 'required|numeric|min:0',
            'iva'        => 'required|numeric|min:0|max:100',
            'min_users'  => 'required|integer|min:1',
            'max_users'  => 'nullable|integer|min:1|gte:min_users',
        ]);

        Plan::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plan creado correctamente'
        ]);
    }

    public function update(Request $request){
        $plan = Plan::findOrFail($request->plan_id);

        $validated = $request->validate([
            'nombre'     => 'required|string|max:255',
            'tipo'       => 'nullable|string|max:100',
            'precio'     => 'required|numeric|min:0',
            'iva'        => 'required|numeric|min:0|max:100',
            'min_users'  => 'required|integer|min:1',
            'max_users'  => 'nullable|integer|min:1|gte:min_users',
        ]);

        $plan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plan actualizado correctamente'
        ]);
    }
    // En app/Http/Controllers/PlanController.php
    public function togglePublic(Request $request){
        $request->validate([
            'id' => 'required|exists:plans,id',
            'public' => 'required|boolean'
        ]);

        $plan = Plan::findOrFail($request->id);

        $plan->update([
            'public' => $request->boolean('public')
        ]);

        return response()->json([
            'success' => true,
            'message' => $plan->public
                ? 'Plan publicado'
                : 'Plan privado',
        ]);
    }
}
