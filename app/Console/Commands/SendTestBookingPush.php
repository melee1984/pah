<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendTestBookingPush extends Command
{
    protected $signature = 'booking:send-test-push
        {token : FCM registration token of the test device}
        {--job-order=TEST-BOOKING : Job order shown in the notification}
        {--credentials= : Path to a Firebase service account JSON file (defaults to GOOGLE_APPLICATION_CREDENTIALS)}';

    protected $description = 'Send a new booking test push notification to one device';

    public function handle(): int
    {
        $path = $this->option('credentials') ?: config('services.firebase.service_account');

        if (! is_string($path) || ! is_readable($path)) {
            $this->error('Provide a readable Firebase service account JSON file with --credentials or GOOGLE_APPLICATION_CREDENTIALS.');

            return self::FAILURE;
        }

        $credentials = json_decode(file_get_contents($path), true);

        if (! is_array($credentials) || empty($credentials['project_id']) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            $this->error('The credentials file must contain project_id, client_email, and private_key.');

            return self::FAILURE;
        }

        $now = time();
        $encode = static fn (array $value): string => rtrim(strtr(base64_encode(json_encode($value, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $header = $encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $claims = $encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]);
        $unsigned = $header.'.'.$claims;

        if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            $this->error('Could not sign the access token request. Check the service account private key.');

            return self::FAILURE;
        }

        $assertion = $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        try {
            $oauth = Http::timeout(15)->asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

            if (! $oauth->successful() || ! is_string($oauth->json('access_token'))) {
                $this->error('Could not obtain a Firebase access token (HTTP '.$oauth->status().'): '.$oauth->json('error_description', $oauth->json('error', 'Unknown error')));

                return self::FAILURE;
            }

            $response = Http::timeout(15)->withToken($oauth->json('access_token'))
                ->post('https://fcm.googleapis.com/v1/projects/'.rawurlencode($credentials['project_id']).'/messages:send', [
                    'message' => [
                        'token' => $this->argument('token'),
                        'notification' => [
                            'title' => 'Pahatud Delivery',
                            'body' => 'You got a new booking: '.$this->option('job-order'),
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                $this->error('Firebase rejected the notification (HTTP '.$response->status().'): '.$response->json('error.message', 'Unknown error'));

                return self::FAILURE;
            }

            $this->info('Firebase accepted the test notification: '.$response->json('name'));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Could not send the notification: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
