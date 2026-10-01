<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyDeletion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Valoración que deja el dueño con el enlace único del último correo.
 */
class FeedbackController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $deletion = $this->find($token);

        return response()->json([
            'company_name' => $deletion->company_name,
            'submitted' => $deletion->feedback_submitted_at !== null,
        ]);
    }

    public function store(Request $request, string $token): JsonResponse
    {
        $deletion = $this->find($token);

        abort_if($deletion->feedback_submitted_at !== null, 409, 'Ya recibimos tu valoración. ¡Gracias!');

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $deletion->forceFill([
            'feedback_rating' => $data['rating'],
            'feedback_comment' => $data['comment'] ?? null,
            'feedback_submitted_at' => now(),
        ])->save();

        return response()->json(['message' => '¡Gracias por tu valoración!']);
    }

    private function find(string $token): CompanyDeletion
    {
        return CompanyDeletion::query()->purged()->where('feedback_token', $token)->firstOrFail();
    }
}
