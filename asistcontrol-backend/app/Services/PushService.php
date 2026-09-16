<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushService
{
    /**
     * Envía una notificación push a una lista de tokens FCM (HTTP v1).
     * Si no hay service account configurada, no hace nada (modo degradado).
     *
     * @param  array<int,string>  $tokens
     */
    public function send(array $tokens, string $title, string $body, array $data = []): int
    {
        $tokens = array_values(array_filter(array_unique($tokens)));

        if (empty($tokens)) {
            return 0;
        }

        $projectId = config('services.fcm.project_id');
        $accessToken = $this->accessToken();

        if (! $projectId || ! $accessToken) {
            Log::info('[PushService] FCM no configurado; se omite el envío.', [
                'tokens' => count($tokens),
                'title' => $title,
            ]);

            return 0;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $sent = 0;

        foreach ($tokens as $token) {
            try {
                $response = Http::withToken($accessToken)->post($url, [
                    'message' => [
                        'token' => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data' => $this->stringifyData($data),
                        'android' => ['priority' => 'high', 'notification' => ['sound' => 'default']],
                        'apns' => ['headers' => ['apns-priority' => '10'], 'payload' => ['aps' => ['sound' => 'default']]],
                    ],
                ]);

                if ($response->successful()) {
                    $sent++;
                } else {
                    Log::warning('[PushService] Error FCM', [
                        'status' => $response->status(),
                        'body' => $response->json() ?? $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('[PushService] Excepción FCM: ' . $e->getMessage());
            }
        }

        return $sent;
    }

    private function accessToken(): ?string
    {
        $serviceAccount = $this->serviceAccount();

        if (! $serviceAccount || empty($serviceAccount['private_key']) || empty($serviceAccount['client_email'])) {
            return null;
        }

        return Cache::remember('fcm_access_token', 3300, function () use ($serviceAccount) {
            $tokenUri = $serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token';
            $now = time();

            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64Url(json_encode([
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $tokenUri,
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $signature = '';
            openssl_sign("{$header}.{$claims}", $signature, $serviceAccount['private_key'], 'sha256WithRSAEncryption');
            $assertion = "{$header}.{$claims}." . $this->base64Url($signature);

            $response = Http::asForm()->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

            if (! $response->successful()) {
                Log::error('[PushService] No se pudo obtener el token de acceso FCM', ['body' => $response->body()]);
                return null;
            }

            return $response->json('access_token');
        });
    }

    private function serviceAccount(): ?array
    {
        $value = config('services.fcm.service_account');

        if (! $value) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_file($value)) {
            return json_decode(file_get_contents($value), true) ?: null;
        }

        return json_decode($value, true) ?: null;
    }

    private function stringifyData(array $data): array
    {
        return array_map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value), $data);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
