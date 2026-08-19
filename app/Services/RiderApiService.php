<?php

namespace App\Services;

use App\Model\Orders\Orders;
use App\Model\Rider\Rider;
use App\RiderApplication;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiderApiService
{
    public const AVAILABILITY_STATES = [
        'offline',
        'available',
        'searching',
        'on_break',
        'active_delivery',
    ];

    public function riderForUser(User $user): ?Rider
    {
        return Rider::query()->where('user_id', $user->id)->first();
    }

    /**
     * @return array{status: string, allowed: bool, message: string}
     */
    public function accountStatus(User $user, ?Rider $rider = null): array
    {
        $rider ??= $this->riderForUser($user);
        $application = RiderApplication::query()
            ->where('email', $user->email)
            ->latest('id')
            ->first();

        if (! $rider) {
            $status = $application?->status ?? 'not_rider';

            return [
                'status' => $status,
                'allowed' => false,
                'message' => $this->statusMessage($status),
            ];
        }

        if (
            (bool) $rider->active
            && ! in_array($application?->status, [
                RiderApplication::STATUS_SUSPENDED,
                RiderApplication::STATUS_EXPIRED_DOCUMENTS,
            ], true)
        ) {
            return [
                'status' => 'approved',
                'allowed' => true,
                'message' => 'Rider account is active.',
            ];
        }

        $status = $application?->status ?? 'suspended';

        return [
            'status' => $status,
            'allowed' => false,
            'message' => $this->statusMessage($status),
        ];
    }

    public function rider(Request $request): Rider
    {
        /** @var Rider $rider */
        $rider = $request->attributes->get('rider');

        return $rider;
    }

    /**
     * @return array<string, mixed>
     */
    public function riderData(User $user, Rider $rider): array
    {
        return [
            'id' => (string) $rider->id,
            'name' => $rider->name ?: trim("{$user->firstname} {$user->lastname}"),
            'email' => $user->email,
            'mobile' => $rider->mobile ?: $user->mobile,
            'active' => (bool) $rider->active,
            'is_active' => (bool) $rider->is_active,
            'date_joined' => $rider->date_join?->toISOString(),
        ];
    }

    public function wallet(int $riderId): object
    {
        DB::table('rider_api_wallets')->insertOrIgnore([
            'rider_id' => $riderId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('rider_api_wallets')->where('rider_id', $riderId)->first();
    }

    public function availability(int $riderId): object
    {
        DB::table('rider_api_availability')->insertOrIgnore([
            'rider_id' => $riderId,
            'state' => 'offline',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('rider_api_availability')->where('rider_id', $riderId)->first();
    }

    public function maskPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        return str_repeat('•', max(0, strlen($phone) - 4)).substr($phone, -4);
    }

    public function maskAddress(?string $address): ?string
    {
        if (! $address) {
            return null;
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $address))));

        return count($parts) > 1 ? implode(', ', array_slice($parts, -2)) : $parts[0];
    }

    public function validDeepLink(?string $deepLink): ?string
    {
        if (! $deepLink) {
            return null;
        }

        $allowed = [
            '#^/app/home$#',
            '#^/delivery/[A-Za-z0-9-]+$#',
            '#^/messages/(support|[A-Za-z0-9-]+)$#',
            '#^/wallet/(payouts|transactions|cod)(/[A-Za-z0-9-]+)?$#',
            '#^/profile/documents$#',
            '#^/approval$#',
        ];

        foreach ($allowed as $pattern) {
            if (preg_match($pattern, $deepLink) === 1) {
                return $deepLink;
            }
        }

        return null;
    }

    private function statusMessage(string $status): string
    {
        return match ($status) {
            'draft' => 'Complete and submit your rider application before signing in.',
            'pending' => 'Your rider application is still under review.',
            'revisions_required' => 'Your rider application requires revisions.',
            'rejected' => 'Your rider application was rejected.',
            'suspended' => 'Your rider account is suspended.',
            'expired_documents' => 'Renew your expired rider documents before signing in.',
            default => 'This account is not an approved rider account.',
        };
    }

    /**
     * @return Collection<int, Orders>
     */
    public function bookings(int $riderId): Collection
    {
        return Orders::query()
            ->with(['cart', 'status'])
            ->whereNotNull('store_accepted_at')
            ->whereNotExists(function ($query) use ($riderId) {
                $query->selectRaw('1')
                    ->from('rider_decline_order')
                    ->whereColumn('rider_decline_order.order_id', 'order.id')
                    ->where('rider_decline_order.rider_id', $riderId);
            })
            ->orderByDesc('submitted_at')
            ->get();
    }
}
