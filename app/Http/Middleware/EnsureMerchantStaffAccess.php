<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\Mobile\Store\OrderController;
use App\Http\Controllers\Api\User\AccessController;
use App\MerchantBranchUser;
use Closure;
use Illuminate\Http\Request;

class EnsureMerchantStaffAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user() ?: $request->user('api');
        abort_unless($user, 401);

        if (! $user->isMerchantStaff()) {
            return $next($request);
        }

        $assignment = MerchantBranchUser::with(['partner', 'branch'])
            ->where('user_id', $user->id)->where('active', true)->first();

        abort_unless($assignment && $assignment->partner && $assignment->branch
            && (int) $assignment->branch->partner_id === (int) $assignment->partner_id,
            403, 'No active branch is assigned to this login.');

        $request->attributes->set('merchantBranchAssignment', $assignment);
        $request->attributes->set('merchantBranch', $assignment->branch);
        $request->attributes->set('merchantAccount', $assignment->partner);

        if ($request->is('api/merchant/*')) {
            $controller = $request->route()->getControllerClass();
            $method = $request->route()->getActionMethod();
            $canReadOrders = $controller === OrderController::class
                && in_array($method, ['orders', 'show'], true)
                && $user->hasPermissionTo('branch.orders.view', 'web');
            $canLogout = $controller === AccessController::class
                && $method === 'postLogout';
            if ($canReadOrders || $canLogout) {
                return $next($request);
            }
        }

        // Staff use dedicated endpoints. Legacy merchant APIs include owner-wide
        // queries and must not become accessible by assigning a staff role.
        if ($request->routeIs('merchant.dashboard.index')) {
            return redirect()->route('merchant.staff.index');
        }

        abort_unless($request->routeIs('merchant.staff.*', 'merchant.password.*'), 403);

        return $next($request);
    }
}
