<?php

namespace App\Services;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;

class AdminCityScope
{
    public function restricted(?User $user = null): bool
    {
        $user ??= Auth::user();

        return $user?->hasRole('manager') === true
            && ! $user->hasAnyRole(['admin', 'super']);
    }

    public function city(?User $user = null): ?string
    {
        $user ??= Auth::user();
        $city = trim((string) $user?->managed_city);

        return $city === '' ? null : mb_strtolower($city);
    }

    public function partners(Builder|QueryBuilder|Relation $query, string $column = 'city'): Builder|QueryBuilder|Relation
    {
        return $this->column($query, $column);
    }

    public function agents(Builder|QueryBuilder|Relation $query, string $column = 'city'): Builder|QueryBuilder|Relation
    {
        if (! $this->restricted()) {
            return $query;
        }

        $city = $this->requiredCity();
        $agentTable = str_contains($column, '.') ? explode('.', $column)[0] : 'agents';

        return $query->where(function ($scope) use ($column, $city, $agentTable) {
            $this->matchingCity($scope, $column, $city)
                ->orWhereExists(function ($restaurants) use ($agentTable, $city) {
                    $restaurants->selectRaw('1')
                        ->from('partners as scoped_partners')
                        ->whereColumn('scoped_partners.agent_id', "{$agentTable}.id");
                    $this->matchingCity($restaurants, 'scoped_partners.city', $city);
                });
        });
    }

    public function riders(Builder|QueryBuilder|Relation $query, string $column = 'city'): Builder|QueryBuilder|Relation
    {
        if (! $this->restricted()) {
            return $query;
        }

        $city = $this->requiredCity();
        $riderTable = str_contains($column, '.') ? explode('.', $column)[0] : 'rider';

        return $query->where(function ($scope) use ($column, $city, $riderTable) {
            $this->matchingCity($scope, $column, $city)
                ->orWhereExists(function ($deliveries) use ($riderTable, $city) {
                    $deliveries->selectRaw('1')
                        ->from('rider_api_deliveries as scoped_deliveries')
                        ->join('order as scoped_orders', 'scoped_orders.id', '=', 'scoped_deliveries.legacy_order_id')
                        ->join('partners as scoped_partners', 'scoped_partners.id', '=', 'scoped_orders.partner_id')
                        ->whereColumn('scoped_deliveries.rider_id', "{$riderTable}.id");
                    $this->matchingCity($deliveries, 'scoped_partners.city', $city);
                });
        });
    }

    public function users(Builder|QueryBuilder|Relation $query, string $column = 'city'): Builder|QueryBuilder|Relation
    {
        if (! $this->restricted()) {
            return $query;
        }

        $city = $this->requiredCity();
        $userTable = str_contains($column, '.') ? explode('.', $column)[0] : 'users';

        return $query->where(function ($scope) use ($column, $city, $userTable) {
            $this->matchingCity($scope, $column, $city)
                ->orWhereExists(function ($orders) use ($userTable, $city) {
                    $orders->selectRaw('1')
                        ->from('order as scoped_orders')
                        ->join('partners as scoped_partners', 'scoped_partners.id', '=', 'scoped_orders.partner_id')
                        ->whereColumn('scoped_orders.user_id', "{$userTable}.id");
                    $this->matchingCity($orders, 'scoped_partners.city', $city);
                });
        });
    }

    public function riderApplications(Builder|QueryBuilder|Relation $query, string $column = 'city'): Builder|QueryBuilder|Relation
    {
        return $this->column($query, $column);
    }

    public function orders(Builder $query): Builder
    {
        if (! $this->restricted()) {
            return $query;
        }

        $city = $this->requiredCity();

        return $query->whereHas('partner', fn (Builder $partner) => $this->matchingCity($partner, 'city', $city));
    }

    public function authorizePartner(?object $partner): void
    {
        $this->authorizeCity($partner->city ?? null);
    }

    public function authorizeMerchantApplication(object $application): void
    {
        $this->authorizeCity($application->city ?? null);
    }

    public function authorizeAgent(object $agent): void
    {
        if (! $this->restricted()) {
            return;
        }

        abort_unless($this->agents($agent->newQuery())->whereKey($agent->getKey())->exists(), 404);
    }

    public function authorizeRider(object $rider): void
    {
        if (! $this->restricted()) {
            return;
        }

        abort_unless($this->riders($rider->newQuery())->whereKey($rider->getKey())->exists(), 404);
    }

    public function authorizeRiderApplication(object $application): void
    {
        $this->authorizeCity($application->city ?? null);
    }

    private function column(Builder|QueryBuilder|Relation $query, string $column): Builder|QueryBuilder|Relation
    {
        if (! $this->restricted()) {
            return $query;
        }

        return $this->matchingCity($query, $column, $this->requiredCity());
    }

    private function matchingCity(Builder|QueryBuilder|Relation $query, string $column, string $city): Builder|QueryBuilder|Relation
    {
        $variants = $this->cityVariants($city);
        $placeholders = implode(', ', array_fill(0, count($variants), '?'));

        return $query->whereRaw("LOWER(TRIM({$column})) IN ({$placeholders})", $variants);
    }

    private function requiredCity(): string
    {
        $city = $this->city();

        abort_if($city === null, 403, 'This manager account has no assigned city.');

        return $city;
    }

    private function authorizeCity(?string $recordCity): void
    {
        if (! $this->restricted()) {
            return;
        }

        abort_unless(
            in_array(mb_strtolower(trim((string) $recordCity)), $this->cityVariants($this->requiredCity()), true),
            404,
        );
    }

    /** @return list<string> */
    private function cityVariants(string $city): array
    {
        $withoutSuffix = preg_replace('/\s+city$/i', '', $city) ?: $city;

        return array_values(array_unique([
            mb_strtolower(trim($city)),
            mb_strtolower(trim($withoutSuffix)),
            mb_strtolower(trim($withoutSuffix.' city')),
        ]));
    }
}
