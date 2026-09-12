<?php

namespace App\Http\Middleware;

use Closure;

class isRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   
        if ($request->header('X-Admin-Request') !== 'apiRequestHandle001') {
            return response()->json([
                'message' => 'Request not allowed',
            ], 403);
        }

        return $next($request); 
    }
}
