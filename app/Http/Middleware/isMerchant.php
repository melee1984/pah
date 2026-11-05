<?php

namespace App\Http\Middleware;


use Closure;
use Auth;
use URL;

class isMerchant
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
        if (Auth::guard('web')->check()) {
                // if (auth()->user()->isMerchantVerified()) {
                    return $next($request);
                // }
                // else {
                //     if ($request->path() == "merchant/verify-status") {
                //         return $next($request);    
                //     } else {
                //         return redirect('merchant/verify-status');
                      
                //     }
                // }
        }
        else {
            return redirect('merchant/dashboard/login');
        }
       
    }
}
