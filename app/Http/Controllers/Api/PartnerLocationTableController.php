<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\PartnerLocation;
use App\Model\Cart;
use App\PartnerLocationCheckoutOption;
use Illuminate\Http\JsonResponse;

class PartnerLocationTableController extends Controller
{
    public function available(PartnerLocation $partnerLocation): JsonResponse
    {
        if (! $partnerLocation->active) {
            return response()->json([
                'status' => 0,
                'message' => 'Merchant location not found.',
            ], 404);
        }

        if (! PartnerLocationCheckoutOption::enabledForLocation(
            $partnerLocation->id,
            Cart::FULFILLMENT_DINE_IN
        )) {
            return response()->json([
                'status' => 1,
                'location' => [
                    'id' => $partnerLocation->id,
                    'partner_id' => $partnerLocation->partner_id,
                    'address' => $partnerLocation->address_1,
                ],
                'tables' => [],
            ]);
        }

        $tables = $partnerLocation->diningTables()
            ->where('active', true)
            ->where('is_available', true)
            ->orderBy('name')
            ->get(['id', 'partner_location_id', 'name', 'capacity']);

        return response()->json([
            'status' => 1,
            'location' => [
                'id' => $partnerLocation->id,
                'partner_id' => $partnerLocation->partner_id,
                'address' => $partnerLocation->address_1,
            ],
            'tables' => $tables,
        ]);
    }
}
