<?php

namespace App\Http\Middleware;

use App\RiderApplication;
use App\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
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
            // Returning riders receive a Sanctum token at login, while the
            // initial registration returns an application token.
            $accessToken = PersonalAccessToken::findToken($token);
            $user = $accessToken?->tokenable;
            $expiration = config('sanctum.expiration');

            if (! $user instanceof User
                || ! $accessToken instanceof PersonalAccessToken
                || ! $accessToken->can('rider:*')
                || ($expiration && $accessToken->created_at->lte(now()->subMinutes($expiration)))
                || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
                return $this->unauthorized();
            }

            $application = RiderApplication::query()
                ->where('email', $user->email)
                ->latest('id')
                ->first();

            if (! $application) {
                return $this->unauthorized();
            }
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
