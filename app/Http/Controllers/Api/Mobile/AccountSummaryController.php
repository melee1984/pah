<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Model\Orders\Orders;
use App\UserFavorite;
use App\UserReward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountSummaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $player = $request->user('api');
        $userId = $player->id;
        $points = (int) UserReward::query()
            ->where('user_id', $userId)
            ->earned()
            ->sum('points');
        $pointTiers = config('rewards.point_tiers', []);
        $pointTier = collect($pointTiers)->first(fn (array $tier) => $points >= $tier['minimum_points']
            && $points <= $tier['maximum_points']);

        // Keep customers above the configured ceiling in the highest available tier.
        $pointTier ??= collect($pointTiers)->last();

        return response()->json([
            'status' => 1,
            'data' => [
                'player' => [
                    'id' => $player->id,
                    'name' => $player->full_name,
                    'firstname' => $player->firstname,
                    'lastname' => $player->lastname,
                    'email' => $player->email,
                    'mobile' => $player->mobile,
                    'photo' => $player->avatar,
                ],
                'total_orders' => Orders::query()
                    ->where('user_id', $userId)
                    ->whereNotNull('submitted_at')
                    ->count(),
                'total_favorites' => UserFavorite::query()
                    ->where('user_id', $userId)
                    ->count(),
                'points' => $points,
                'point_tier' => $pointTier,
                'point_tiers' => $pointTiers,
            ],
        ]);
    }
}
