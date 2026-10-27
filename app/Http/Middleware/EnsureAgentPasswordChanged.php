<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgentPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('agent')?->must_change_password) {
            return redirect()->route('agent.password.edit');
        }

        return $next($request);
    }
}
