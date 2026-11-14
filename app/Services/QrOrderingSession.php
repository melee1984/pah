<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

class QrOrderingSession
{
    private const PURPOSE = 'qr-ordering-session-v1';

    public function issue(string $qrUuid, int $branchId): array
    {
        $sessionId = (string) Str::uuid();
        $expiresAt = now()->addMinutes(max(5, (int) config('services.qr_ordering.session_ttl_minutes', 720)));
        $payload = [
            'purpose' => self::PURPOSE,
            'qr_uuid' => $qrUuid,
            'branch_id' => $branchId,
            'session_id' => $sessionId,
            'expires_at' => $expiresAt->getTimestamp(),
        ];

        return [
            'session_id' => $sessionId,
            'token' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    public function isValid(
        string $token,
        string $qrUuid,
        int $branchId,
        string $sessionId
    ): bool {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return false;
        }

        return is_array($payload)
            && ($payload['purpose'] ?? null) === self::PURPOSE
            && isset($payload['qr_uuid'], $payload['branch_id'], $payload['session_id'], $payload['expires_at'])
            && hash_equals((string) $payload['qr_uuid'], $qrUuid)
            && (int) $payload['branch_id'] === $branchId
            && hash_equals((string) $payload['session_id'], $sessionId)
            && Carbon::createFromTimestamp((int) $payload['expires_at'])->isFuture();
    }
}
