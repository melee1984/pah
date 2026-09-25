<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\UserReward;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserRewardReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['points_desc', 'points_asc', 'latest', 'customer'])],
        ]);

        $search = trim($validated['search'] ?? '');
        $sort = $validated['sort'] ?? 'points_desc';
        $baseQuery = UserReward::query()
            ->join('users', 'users.id', '=', 'user_rewards.user_id')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('users.firstname', 'like', "%{$search}%")
                        ->orWhere('users.lastname', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('users.mobile', 'like', "%{$search}%");
                });
            });

        $earnedQuery = (clone $baseQuery)->where('user_rewards.status', UserReward::STATUS_EARNED);
        $reversedQuery = (clone $baseQuery)->where('user_rewards.status', UserReward::STATUS_REVERSED);
        $metrics = [
            'available_points' => (int) (clone $earnedQuery)->sum('user_rewards.points'),
            'customers' => (clone $baseQuery)->distinct('user_rewards.user_id')->count('user_rewards.user_id'),
            'rewarded_orders' => (clone $earnedQuery)->count(),
            'reversed_points' => (int) (clone $reversedQuery)->sum('user_rewards.points'),
            'qualifying_spend' => (float) (clone $earnedQuery)->sum('user_rewards.order_amount'),
        ];

        $customers = $baseQuery
            ->select([
                'users.id as user_id',
                'users.firstname',
                'users.lastname',
                'users.email',
                'users.mobile',
            ])
            ->selectRaw(
                'SUM(CASE WHEN user_rewards.status = ? THEN user_rewards.points ELSE 0 END) as available_points',
                [UserReward::STATUS_EARNED],
            )
            ->selectRaw('SUM(user_rewards.points) as lifetime_points')
            ->selectRaw(
                'SUM(CASE WHEN user_rewards.status = ? THEN user_rewards.points ELSE 0 END) as reversed_points',
                [UserReward::STATUS_REVERSED],
            )
            ->selectRaw(
                'SUM(CASE WHEN user_rewards.status = ? THEN user_rewards.order_amount ELSE 0 END) as qualifying_spend',
                [UserReward::STATUS_EARNED],
            )
            ->selectRaw(
                'SUM(CASE WHEN user_rewards.status = ? THEN 1 ELSE 0 END) as rewarded_orders',
                [UserReward::STATUS_EARNED],
            )
            ->selectRaw('MAX(user_rewards.earned_at) as latest_reward_at')
            ->groupBy([
                'users.id',
                'users.firstname',
                'users.lastname',
                'users.email',
                'users.mobile',
            ]);

        match ($sort) {
            'points_asc' => $customers->orderBy('available_points')->orderBy('users.firstname'),
            'latest' => $customers->orderByDesc('latest_reward_at'),
            'customer' => $customers->orderBy('users.firstname')->orderBy('users.lastname'),
            default => $customers->orderByDesc('available_points')->orderBy('users.firstname'),
        };

        return view('dashboard.pages.report.user-rewards', [
            'customers' => $customers->paginate(25)->withQueryString(),
            'metrics' => $metrics,
            'search' => $search,
            'sort' => $sort,
        ]);
    }
}
