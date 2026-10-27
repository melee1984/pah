<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebasePush
{
    /** @param array<string, string> $data */
    public function send(
        string $deviceToken,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null,
    ): string {
        $credentials = $this->credentials();
        $accessToken = Cache::remember(
            'firebase-messaging-token:'.$credentials['project_id'],
            now()->addMinutes(50),
            fn () => $this->accessToken($credentials),
        );

        $notification = array_filter([
            'title' => $title,
            'body' => $body,
            'image' => $imageUrl,
        ], fn ($value) => $value !== null && $value !== '');

        $message = [
            'token' => $deviceToken,
            'notification' => $notification,
            'data' => collect($data)->map(fn ($value) => (string) $value)->all(),
            'android' => ['priority' => 'HIGH'],
            'apns' => [
                'payload' => ['aps' => ['sound' => 'default', 'mutable-content' => 1]],
            ],
        ];

        if ($imageUrl) {
            $message['android']['notification'] = ['image' => $imageUrl];
            $message['apns']['fcm_options'] = ['image' => $imageUrl];
        }

        $response = Http::timeout(15)->withToken($accessToken)
            ->post('https://fcm.googleapis.com/v1/projects/'.rawurlencode($credentials['project_id']).'/messages:send', [
                'message' => $message,
            ]);

        if (! $response->successful()) {
            $providerMessage = $response->json('error.message');
            $detail = is_string($providerMessage) && $providerMessage !== ''
                ? ': '.mb_substr($providerMessage, 0, 350)
                : '';

            throw new RuntimeException('Firebase rejected the notification (HTTP '.$response->status().')'.$detail);
        }

        return (string) $response->json('name', 'accepted');
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
