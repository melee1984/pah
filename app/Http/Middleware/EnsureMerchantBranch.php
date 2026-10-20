<?php

namespace App\Http\Middleware;

use App\MerchantBranchUser;
use Closure;
use Illuminate\Http\Request;

class EnsureMerchantBranch
{
    public function handle(Request $request, Closure $next)
    {
        $assignment = MerchantBranchUser::query()
            ->with(['partner', 'branch'])
            ->where('user_id', $request->user()?->id)
            ->where('active', true)
            ->first();

        if (! $assignment
            || ! $assignment->partner
            || ! $assignment->branch
            || (int) $assignment->branch->partner_id !== (int) $assignment->partner_id) {
            return response()->json([
                'status' => 0,
                'message' => 'No active merchant branch is assigned to this login.',
            ], 403);
        }

        $request->attributes->set('merchantBranchAssignment', $assignment);
        $request->attributes->set('merchantBranch', $assignment->branch);
        $request->merge([
            'merchant_location_id' => $assignment->partner_location_id,
            'store_location_id' => $assignment->partner_location_id,
        ]);

        return $next($request);
    }
}
