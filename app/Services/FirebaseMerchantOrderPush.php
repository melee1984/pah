<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseMerchantOrderPush
{
    public function send(string $deviceToken, string $orderNo, int $orderId, int $locationId): void
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
                    'notification' => [
                        'title' => 'PahatudFood Delivery',
                        'body' => 'We have a new order for you Job Order '.$orderNo,
                    ],
                    'data' => [
                        'type' => 'new_order',
                        'order_id' => (string) $orderId,
                        'order_no' => $orderNo,
                        'partner_location_id' => (string) $locationId,
                    ],
                    'android' => ['priority' => 'HIGH'],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Firebase rejected the merchant order notification (HTTP '.$response->status().').');
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

        if (! is_array($credentials) || empty($credentials['project_id'])
            || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('Firebase service account credentials are invalid.');
        }

        return $credentials;
    }

    /** @param array{project_id: string, client_email: string, private_key: string} $credentials */
    private function accessToken(array $credentials): string
    {
        $encode = static fn (array $value): string => rtrim(strtr(base64_encode(json_encode($value, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $now = time();
        $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
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
