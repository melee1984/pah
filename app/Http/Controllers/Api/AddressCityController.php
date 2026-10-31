<?php

namespace App\Http\Controllers\Api;

use App\City;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AddressCityController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'cities' => City::query()
                ->orderBy('name')
                ->get(['id', 'name', 'active']),
        ]);
    }
}
