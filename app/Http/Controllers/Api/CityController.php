<?php

namespace App\Http\Controllers\Api;

use App\City;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'cities' => City::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
