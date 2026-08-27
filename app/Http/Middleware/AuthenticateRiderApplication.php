<?php

namespace App\Http\Middleware;

use App\RiderApplication;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateRiderApplication
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return $this->unauthorized();
        }

        $application = RiderApplication::query()
            ->where('access_token_hash', hash('sha256', $token))
            ->first();

        if (! $application) {
            return $this->unauthorized();
        }

        $request->attributes->set('rider_application', $application);

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json([
            'message' => 'Invalid or missing rider application token.',
        ], 401);
    }
}
