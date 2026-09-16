<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TurnstileService
{
    public function verify(string $token, ?string $ipAddress, string $expectedAction): bool
    {
        $secret = config('services.turnstile.secret');

        if (! is_string($secret) || $secret === '') {
            Log::error('Turnstile validation is enabled without a secret key.');

            return false;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(8)
                ->post(config('services.turnstile.verify_url'), array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]));
        } catch (Throwable $exception) {
            Log::warning('Turnstile Siteverify request failed.', [
                'exception' => $exception::class,
            ]);

            return false;
        }

        $result = $response->json();
        $verified = $response->successful()
            && is_array($result)
            && ($result['success'] ?? false) === true
            && ($result['action'] ?? null) === $expectedAction;

        if (! $verified) {
            Log::notice('Turnstile rejected a public form submission.', [
                'action' => $expectedAction,
                'response_action' => is_array($result) ? ($result['action'] ?? null) : null,
                'error_codes' => is_array($result) ? ($result['error-codes'] ?? []) : [],
                'http_status' => $response->status(),
            ]);
        }

        return $verified;
    }
}
