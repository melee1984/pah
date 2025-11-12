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
        if ($request->input('admin_request') != 'apiRequestHandle001') {
            echo "Request not allowed";
            die();
        }
        return $next($request);  
    }
}
