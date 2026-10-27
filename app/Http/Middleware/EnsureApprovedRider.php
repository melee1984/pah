<?php

namespace App\Http\Middleware;

use App\Services\RiderApiService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedRider
{
    public function __construct(private readonly RiderApiService $riders) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $rider = $user ? $this->riders->riderForUser($user) : null;
        $status = $user ? $this->riders->accountStatus($user, $rider) : null;

        if (! $user || ! $rider || ! $status['allowed']) {
            return $this->forbidden(
                $status['status'] ?? 'unauthenticated',
                $status['message'] ?? 'Unauthenticated.',
            );
        }

        $request->attributes->set('rider', $rider);

        return $next($request);
    }

    private function forbidden(string $status, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'account_status' => $status,
            'capabilities' => [
                'can_go_online' => false,
                'can_accept_offers' => false,
                'can_start_delivery' => false,
            ],
        ], 403);
    }
}
