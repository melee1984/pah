<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\UserFavorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favoriteIds = UserFavorite::query()
            ->where('user_id', $request->user('api')->id)
            ->latest('updated_at')
            ->pluck('partner_id')
            ->map(fn ($partnerId) => (string) $partnerId)
            ->values();

        return response()->json([
            'status' => 1,
            'data' => ['favorite_ids' => $favoriteIds],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'partner_id' => ['required', 'integer', 'exists:partners,id'],
            'is_favorite' => ['required', 'boolean'],
        ]);

        $attributes = [
            'user_id' => $request->user('api')->id,
            'partner_id' => $validated['partner_id'],
        ];

        if ($validated['is_favorite']) {
            UserFavorite::query()->firstOrCreate($attributes);
        } else {
            UserFavorite::query()->where($attributes)->delete();
        }

        return response()->json([
            'status' => 1,
            'message' => $validated['is_favorite']
                ? 'Restaurant added to favorites.'
                : 'Restaurant removed from favorites.',
            'data' => [
                'partner_id' => (string) $validated['partner_id'],
                'is_favorite' => (bool) $validated['is_favorite'],
            ],
        ]);
    }
}
