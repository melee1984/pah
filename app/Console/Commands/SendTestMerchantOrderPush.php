<?php

namespace App\Console\Commands;

use App\PartnerLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendTestMerchantOrderPush extends Command
{
    protected $signature = 'merchant:send-test-order-push
        {token? : FCM registration token of the merchant test device}
        {--location-id= : Use the device token registered by this merchant location}
        {--job-order=TEST-ORDER : Job order number shown in the notification}
        {--credentials= : Path to a Firebase service account JSON file (defaults to GOOGLE_APPLICATION_CREDENTIALS)}';

    protected $description = 'Send a new-order test push notification to one merchant device';

    public function handle(): int
    {
        $token = $this->argument('token');
        $locationId = $this->option('location-id');

        if (($token === null || $token === '') === ($locationId === null || $locationId === '')) {
            $this->error('Provide exactly one of: a device token or --location-id.');

            return self::FAILURE;
        }

        if ($locationId !== null && $locationId !== '') {
            if (! ctype_digit((string) $locationId) || (int) $locationId < 1) {
                $this->error('Location ID must be a positive integer.');

                return self::FAILURE;
            }

            try {
                $location = PartnerLocation::query()->find($locationId);
            } catch (Throwable $exception) {
                $this->error('Could not look up the merchant location. Check the database connection.');

                return self::FAILURE;
            }

            $token = $location?->device_token;

            if (! is_string($token) || trim($token) === '') {
                $this->error('No device token is registered for that merchant location.');

                return self::FAILURE;
            }
        }

        $jobOrder = trim((string) $this->option('job-order'));

        if ($jobOrder === '') {
            $this->error('Job order number cannot be empty.');

            return self::FAILURE;
        }

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
        $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

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
                        'token' => $token,
                        'notification' => [
                            'title' => 'PahatudFood Delivery',
                            'body' => 'We have a new order for you Job Order '.$jobOrder,
                        ],
                        'data' => [
                            'type' => 'new_order',
                            'order_no' => $jobOrder,
                        ],
                        'android' => ['priority' => 'HIGH'],
                    ],
                ]);

            if (! $response->successful()) {
                $this->error('Firebase rejected the notification (HTTP '.$response->status().'): '.$response->json('error.message', 'Unknown error'));

                return self::FAILURE;
            }

            $this->info('Firebase accepted the merchant test notification: '.$response->json('name'));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Could not send the notification: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
