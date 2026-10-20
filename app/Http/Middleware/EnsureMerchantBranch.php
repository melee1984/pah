<?php

namespace App\Http\Middleware;

use App\MerchantBranchUser;
use App\PartnerLocation;
use App\Partners;
use Closure;
use Illuminate\Http\Request;

class EnsureMerchantBranch
{
    public function handle(Request $request, Closure $next)
    {
        $merchant = Partners::query()
            ->with('locations')
            ->where('user_id', $request->user()?->id)
            ->first();

        if ($merchant) {
            $requestedBranchId = $request->input('merchant_location_id')
                ?: $request->input('store_location_id');

            if (! $requestedBranchId && $merchant->locations->count() === 1) {
                $requestedBranchId = $merchant->locations->first()->id;
            }

            $branch = $requestedBranchId
                ? PartnerLocation::query()
                    ->where('partner_id', $merchant->id)
                    ->find($requestedBranchId)
                : null;

            if ($requestedBranchId && ! $branch) {
                return response()->json([
                    'status' => 0,
                    'message' => 'The selected branch does not belong to this merchant.',
                ], 403);
            }

            $request->attributes->set('merchantAccessType', 'parent');
            $request->attributes->set('merchantAccount', $merchant);

            if ($branch) {
                $request->attributes->set('merchantBranch', $branch);
                $request->merge([
                    'merchant_location_id' => $branch->id,
                    'store_location_id' => $branch->id,
                ]);
            }

            return $next($request);
        }

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
                'message' => 'No merchant or active merchant branch is assigned to this login.',
            ], 403);
        }

        $request->attributes->set('merchantBranchAssignment', $assignment);
        $request->attributes->set('merchantBranch', $assignment->branch);
        $request->attributes->set('merchantAccessType', 'branch');
        $request->attributes->set('merchantAccount', $assignment->partner);
        $request->merge([
            'merchant_location_id' => $assignment->partner_location_id,
            'store_location_id' => $assignment->partner_location_id,
        ]);

        return $next($request);
    }
}
