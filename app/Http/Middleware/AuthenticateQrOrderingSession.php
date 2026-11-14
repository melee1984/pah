<?php

namespace App\Http\Middleware;

use App\Services\QrOrderingSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateQrOrderingSession
{
    public function __construct(private readonly QrOrderingSession $sessions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->header('X-QR-Session-Token', '');
        $qrUuid = (string) $request->route('uuid', '');
        $branchId = (int) $request->route('branchId', 0);
        $sessionId = (string) $request->route('sessionId', '');

        if (! $token || ! $this->sessions->isValid($token, $qrUuid, $branchId, $sessionId)) {
            return response()->json([
                'status' => 0,
                'message' => 'This QR ordering session is missing, invalid, or expired. Please scan the QR code again.',
            ], 401);
        }

        return $next($request);
    }
}
