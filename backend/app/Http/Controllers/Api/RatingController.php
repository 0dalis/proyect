<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Opiniones sobre AsistControl: el dueño y los administradores califican de
 * 1 a 5 estrellas (con medias). El Super Admin decide cuáles salen en la landing.
 */
class RatingController extends Controller
{
    public const TESTIMONIALS_CACHE = 'landing.testimonials';

    public function show(Request $request): JsonResponse
    {
        $rating = Rating::query()->where('user_id', $request->user()->id)->first();

        return response()->json(['rating' => $rating ? $this->mine($rating) : null]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            // 1, 1.5, 2 ... 5
            'score' => ['required', 'numeric', 'between:1,5', 'multiple_of:0.5'],
            'comment' => ['nullable', 'string', 'max:500'],
            'allow_publish' => ['boolean'],
        ]);

        $user = $request->user();
        $rating = Rating::query()->firstOrNew(['user_id' => $user->id]);
        $comment = filled($data['comment'] ?? null) ? trim($data['comment']) : null;
        $allowPublish = (bool) ($data['allow_publish'] ?? false);

        // Si cambia lo que se mostraría, vuelve a revisión
        $changed = ! $rating->exists
            || (float) $rating->score !== (float) $data['score']
            || $rating->comment !== $comment
            || $rating->allow_publish !== $allowPublish;

        $rating->fill([
            'company_id' => $user->company_id,
            'score' => $data['score'],
            'comment' => $comment,
            'allow_publish' => $allowPublish,
        ]);

        if ($changed) {
            $rating->forceFill(['status' => Rating::PENDING, 'published_at' => null]);
        }

        $rating->save();
        Cache::forget(self::TESTIMONIALS_CACHE);

        return response()->json([
            'message' => '¡Gracias por tu opinión!',
            'rating' => $this->mine($rating),
        ]);
    }

    /**
     * Públicas: solo las aprobadas y con permiso del usuario para mostrarlas.
     */
    public function testimonials(): JsonResponse
    {
        return response()->json(Cache::remember(self::TESTIMONIALS_CACHE, 600, function () {
            $published = Rating::query()->published()->with(['user', 'company'])->latest('published_at')->limit(12)->get();

            return [
                'average' => round((float) Rating::query()->avg('score'), 1),
                'count' => Rating::query()->count(),
                'items' => $published->map(fn (Rating $rating) => [
                    'id' => $rating->id,
                    'score' => $rating->score,
                    'comment' => $rating->comment,
                    // Nombre y primera letra del apellido
                    'name' => self::shortName($rating->user->name),
                    'role' => $rating->user->is_owner ? 'Dueño' : 'Administrador',
                    'company' => $rating->company->name,
                ])->values(),
            ];
        }));
    }

    public static function shortName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return count($parts) > 1 ? $parts[0].' '.Str::upper(Str::substr($parts[1], 0, 1)).'.' : ($parts[0] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function mine(Rating $rating): array
    {
        return [
            'score' => $rating->score,
            'comment' => $rating->comment,
            'allow_publish' => $rating->allow_publish,
            'status' => $rating->status,
            'updated_at' => $rating->updated_at,
        ];
    }
}
