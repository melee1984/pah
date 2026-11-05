<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMerchantPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('web')?->must_change_password && ! $request->routeIs('merchant.password.*')) {
            return redirect()->route('merchant.password.edit');
        }

        return $next($request);
    }
}
