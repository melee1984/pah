<?php

namespace App\Services;

use App\AdminPushNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminPushRecipientResolver
{
    /**
     * @param  array<int, int|string>  $targetIds
     * @return Collection<int, array{type: string, id: int, label: string, token: string}>
     */
    public function resolve(string $audience, array $targetIds = [], string $category = AdminPushNotification::CATEGORY_GENERAL): Collection
    {
        $recipients = match ($audience) {
            AdminPushNotification::AUDIENCE_ALL_USERS => $this->users(),
            AdminPushNotification::AUDIENCE_SELECTED_USERS => $targetIds === [] ? collect() : $this->users($targetIds),
            AdminPushNotification::AUDIENCE_ALL_RIDERS => $this->riders([], $category),
            AdminPushNotification::AUDIENCE_SELECTED_RIDERS => $targetIds === [] ? collect() : $this->riders($targetIds, $category),
            AdminPushNotification::AUDIENCE_MERCHANTS => $this->merchants(),
            default => collect(),
        };

        return $recipients
            ->filter(fn (array $recipient) => trim($recipient['token']) !== '')
            ->unique(fn (array $recipient) => hash('sha256', $recipient['token']))
            ->values();
    }

    /** @param array<int, int|string> $targetIds */
    public function count(string $audience, array $targetIds = [], string $category = AdminPushNotification::CATEGORY_GENERAL): int
    {
        return $this->resolve($audience, $targetIds, $category)->count();
    }

    /** @return Collection<int, object> */
    public function selectableUsers(): Collection
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'device_token_food')) {
            return collect();
        }

        return DB::table('users')
            ->whereNotNull('device_token_food')
            ->where('device_token_food', '!=', '')
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get(['id', 'firstname', 'lastname', 'email', 'mobile']);
    }

    /** @return Collection<int, object> */
    public function selectableRiders(): Collection
    {
        if (! Schema::hasTable('rider') || ! Schema::hasTable('rider_api_devices')) {
            return collect();
        }

        return DB::table('rider')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rider_api_devices')
                    ->whereColumn('rider_api_devices.rider_id', 'rider.id')
                    ->whereNull('rider_api_devices.revoked_at')
                    ->whereNotNull('rider_api_devices.push_token')
                    ->where('rider_api_devices.push_token', '!=', '');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'mobile']);
    }

    /** @param array<int, int|string> $ids */
    private function users(array $ids = []): Collection
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'device_token_food')) {
            return collect();
        }

        $query = DB::table('users')
            ->whereNotNull('device_token_food')
            ->where('device_token_food', '!=', '');

        if ($ids !== []) {
            $query->whereIn('id', $this->normaliseIds($ids));
        }

        return $query->get(['id', 'firstname', 'lastname', 'email', 'device_token_food'])
            ->map(fn ($user) => [
                'type' => 'user',
                'id' => (int) $user->id,
                'label' => trim($user->firstname.' '.$user->lastname) ?: ($user->email ?: 'User #'.$user->id),
                'token' => (string) $user->device_token_food,
            ]);
    }

    /** @param array<int, int|string> $ids */
    private function riders(array $ids, string $category): Collection
    {
        if (! Schema::hasTable('rider') || ! Schema::hasTable('rider_api_devices')) {
            return collect();
        }

        $query = DB::table('rider_api_devices as devices')
            ->join('rider', 'rider.id', '=', 'devices.rider_id')
            ->whereNull('devices.revoked_at')
            ->whereNotNull('devices.push_token')
            ->where('devices.push_token', '!=', '');

        if ($ids !== []) {
            $query->whereIn('rider.id', $this->normaliseIds($ids));
        }

        if ($category === AdminPushNotification::CATEGORY_PROMOTION
            && Schema::hasTable('rider_api_notification_preferences')) {
            $query->join('rider_api_notification_preferences as preferences', 'preferences.rider_id', '=', 'rider.id')
                ->where('preferences.marketing', true);
        }

        return $query->get(['rider.id', 'rider.name', 'rider.mobile', 'devices.push_token'])
            ->map(function ($rider) {
                try {
                    $token = Crypt::decryptString($rider->push_token);
                } catch (Throwable) {
                    return null;
                }

                return [
                    'type' => 'rider',
                    'id' => (int) $rider->id,
                    'label' => $rider->name ?: ($rider->mobile ?: 'Rider #'.$rider->id),
                    'token' => $token,
                ];
            })
            ->filter()
            ->values();
    }

    private function merchants(): Collection
    {
        if (! Schema::hasTable('partner_location') || ! Schema::hasColumn('partner_location', 'device_token')) {
            return collect();
        }

        $query = DB::table('partner_location as locations')
            ->whereNotNull('locations.device_token')
            ->where('locations.device_token', '!=', '');

        if (Schema::hasTable('partners')) {
            $query->leftJoin('partners', 'partners.id', '=', 'locations.partner_id');
        }

        return $query->get([
            'locations.id',
            'locations.address_1',
            'locations.device_token',
            Schema::hasTable('partners') ? 'partners.restaurant_name' : DB::raw('NULL as restaurant_name'),
        ])->map(fn ($location) => [
            'type' => 'merchant',
            'id' => (int) $location->id,
            'label' => $location->restaurant_name ?: ($location->address_1 ?: 'Merchant location #'.$location->id),
            'token' => (string) $location->device_token,
        ]);
    }

    /** @param array<int, int|string> $ids */
    private function normaliseIds(array $ids): array
    {
        return collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }
}
