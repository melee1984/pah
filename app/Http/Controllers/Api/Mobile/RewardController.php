<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\UserReward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user('api')->id;
        $perPage = min(max($request->integer('per_page', 20), 1), 100);
        $points = (int) UserReward::query()
            ->where('user_id', $userId)
            ->earned()
            ->sum('points');
        $rewards = UserReward::query()
            ->where('user_id', $userId)
            ->latest('earned_at')
            ->paginate($perPage, [
                'id',
                'order_id',
                'points',
                'order_amount',
                'php_per_point',
                'status',
                'earned_at',
                'reversed_at',
            ]);

        return response()->json([
            'status' => 1,
            'data' => [
                'points' => $points,
                'php_per_point' => (float) config('rewards.php_per_point', 100),
                'currency' => 'PHP',
                'rewards' => $rewards->items(),
                'pagination' => [
                    'current_page' => $rewards->currentPage(),
                    'last_page' => $rewards->lastPage(),
                    'per_page' => $rewards->perPage(),
                    'total' => $rewards->total(),
                ],
            ],
        ]);
    }
}
