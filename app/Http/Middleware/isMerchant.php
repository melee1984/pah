<?php

namespace App\Http\Middleware;

use App\Partners;
use Closure;
use Illuminate\Http\Request;

class isMerchant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('web');
        if (! $user) {
            return redirect('merchant/dashboard/login');
        }

        abort_unless($user->isMerchant() || Partners::where('user_id', $user->id)->exists(), 403);

        return app(EnsureMerchantStaffAccess::class)->handle($request, $next);
    }
}
