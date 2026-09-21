<?php

namespace App\Http\Middleware;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ValidateTurnstile
{
    public function __construct(private readonly TurnstileService $turnstile) {}

    public function handle(Request $request, Closure $next, string $action): Response
    {
        if (! config('services.turnstile.enabled')) {
            return $next($request);
        }

        $validated = $request->validate([
            'cf-turnstile-response' => ['bail', 'required', 'string', 'max:2048'],
        ], [
            'cf-turnstile-response.required' => 'Please complete the security verification.',
            'cf-turnstile-response.max' => 'The security verification response is invalid. Please try again.',
        ]);

        if (! $this->turnstile->verify(
            $validated['cf-turnstile-response'],
            $request->ip(),
            $action,
        )) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Security verification failed or expired. Please try again.',
            ]);
        }

        return $next($request);
    }
}
