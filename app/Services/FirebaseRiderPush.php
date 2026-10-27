<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseRiderPush
{
    /** @param array<string, string> $data */
    public function send(string $deviceToken, string $title, string $body, array $data): void
    {
        $credentials = $this->credentials();
        $accessToken = Cache::remember(
            'firebase-messaging-token:'.$credentials['project_id'],
            now()->addMinutes(50),
            fn () => $this->accessToken($credentials),
        );

        $response = Http::timeout(10)->withToken($accessToken)
            ->post('https://fcm.googleapis.com/v1/projects/'.rawurlencode($credentials['project_id']).'/messages:send', [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => $data,
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'channel_id' => 'new_bookings_v1',
                            'sound' => 'new_booking_alert',
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Firebase rejected the offer notification (HTTP '.$response->status().').');
        }
    }

    /** @return array{project_id: string, client_email: string, private_key: string} */
    private function credentials(): array
    {
        $path = config('services.firebase.service_account');
        if (! is_string($path) || ! is_readable($path)) {
            throw new RuntimeException('Firebase service account credentials are not configured.');
        }

        $credentials = json_decode(file_get_contents($path), true);
        if (! is_array($credentials)
            || empty($credentials['project_id'])
            || empty($credentials['client_email'])
            || empty($credentials['private_key'])) {
            throw new RuntimeException('Firebase service account credentials are invalid.');
        }

        return $credentials;
    }

    /** @param array{project_id: string, client_email: string, private_key: string} $credentials */
    private function accessToken(array $credentials): string
    {
        $encode = static fn (array $value): string => rtrim(strtr(base64_encode(json_encode($value, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => time(),
            'exp' => time() + 3600,
        ]);
        if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Could not sign Firebase access token request.');
        }

        $assertion = $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $response = Http::timeout(10)->asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ]);
        $token = $response->json('access_token');
        if (! $response->successful() || ! is_string($token) || $token === '') {
            throw new RuntimeException('Could not obtain a Firebase access token (HTTP '.$response->status().').');
        }

        return $token;
    }
}
