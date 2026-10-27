<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Cart;
use App\PartnerLocation;
use App\PartnerLocationCheckoutOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PartnerLocationCheckoutOptionController extends Controller
{
    public function available(PartnerLocation $partnerLocation): JsonResponse
    {
        abort_unless($partnerLocation->active, 404);

        return response()->json([
            'status' => 1,
            'location_id' => $partnerLocation->id,
            'options' => PartnerLocationCheckoutOption::availableForLocation($partnerLocation->id),
        ]);
    }

    public function update(PartnerLocation $partnerLocation, Request $request): JsonResponse
    {
        $user = $request->user('api') ?: $request->user();

        if (! $user) {
            return response()->json(['status' => 0, 'message' => 'Unauthenticated.'], 401);
        }

        if (! $user->merchant || (int) $user->merchant->id !== (int) $partnerLocation->partner_id) {
            return response()->json(['status' => 0, 'message' => 'This location does not belong to your merchant account.'], 403);
        }

        $validated = $request->validate([
            'options' => ['required', 'array', 'min:1'],
            'options.*' => ['required', 'string', 'distinct', Rule::in(Cart::FULFILLMENT_TYPES)],
        ]);

        DB::transaction(function () use ($partnerLocation, $validated) {
            foreach (Cart::FULFILLMENT_TYPES as $type) {
                PartnerLocationCheckoutOption::query()->updateOrCreate([
                    'partner_location_id' => $partnerLocation->id,
                    'type' => $type,
                ], [
                    'active' => in_array($type, $validated['options'], true),
                ]);
            }
        });

        return response()->json([
            'status' => 1,
            'message' => 'Checkout options updated.',
            'location_id' => $partnerLocation->id,
            'options' => PartnerLocationCheckoutOption::availableForLocation($partnerLocation->id),
        ]);
    }
}
