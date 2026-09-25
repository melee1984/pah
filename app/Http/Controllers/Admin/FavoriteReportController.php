<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Partners;
use App\UserFavorite;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FavoriteReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'merchant_id' => ['nullable', 'integer', 'exists:partners,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $selectedMerchantId = isset($validated['merchant_id'])
            ? (int) $validated['merchant_id']
            : null;
        $search = trim($validated['search'] ?? '');
        $from = isset($validated['from'])
            ? Carbon::createFromFormat('Y-m-d', $validated['from'])->startOfDay()
            : null;
        $to = isset($validated['to'])
            ? Carbon::createFromFormat('Y-m-d', $validated['to'])->endOfDay()
            : null;

        $baseQuery = UserFavorite::query()
            ->when($selectedMerchantId, fn ($query) => $query->where('partner_id', $selectedMerchantId))
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('partner', fn ($partnerQuery) => $partnerQuery
                        ->where('restaurant_name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('firstname', 'like', "%{$search}%")
                            ->orWhere('lastname', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%"));
                });
            });

        $metrics = [
            'favorites' => (clone $baseQuery)->count(),
            'customers' => (clone $baseQuery)->distinct('user_id')->count('user_id'),
            'merchants' => (clone $baseQuery)->distinct('partner_id')->count('partner_id'),
            'this_month' => (clone $baseQuery)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
        ];

        $topMerchants = (clone $baseQuery)
            ->select([
                'partner_id',
                DB::raw('COUNT(*) as favorites_count'),
                DB::raw('MAX(created_at) as latest_favorite_at'),
            ])
            ->with('partner:id,restaurant_name,active')
            ->groupBy('partner_id')
            ->orderByDesc('favorites_count')
            ->orderByDesc('latest_favorite_at')
            ->limit(10)
            ->get();

        $favorites = $baseQuery
            ->with([
                'partner:id,restaurant_name,active',
                'user:id,firstname,lastname,email,mobile',
            ])
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('dashboard.pages.report.favorites', [
            'favorites' => $favorites,
            'topMerchants' => $topMerchants,
            'merchants' => Partners::query()
                ->orderBy('restaurant_name')
                ->get(['id', 'restaurant_name']),
            'metrics' => $metrics,
            'selectedMerchantId' => $selectedMerchantId,
            'search' => $search,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
