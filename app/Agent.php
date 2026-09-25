<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Agent extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'commission_percentage',
        'active',
        'review_status',
        'review_message',
        'reviewed_at',
        'last_login_at',
        'must_change_password',
        'temporary_password_created_at',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'reviewed_at' => 'datetime',
            'must_change_password' => 'boolean',
            'commission_percentage' => 'decimal:2',
            'last_login_at' => 'datetime',
            'temporary_password_created_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Partners::class, 'agent_id');
    }

    public function approvedRestaurants(): HasMany
    {
        return $this->restaurants()->where('application_status', 'approved');
    }

    public function approvedRestaurantCount(): int
    {
        $loadedCount = $this->getAttribute('approved_restaurants_count');

        return $loadedCount === null
            ? $this->approvedRestaurants()->count()
            : (int) $loadedCount;
    }

    public function commissionPercentage(?int $approvedRestaurantCount = null): float
    {
        $approvedRestaurantCount ??= $this->approvedRestaurantCount();
        $percentage = 0.0;
        $tiers = config('agent.commission_tiers', [0 => 15, 35 => 20, 50 => 30]);
        ksort($tiers, SORT_NUMERIC);

        foreach ($tiers as $minimumRestaurants => $tierPercentage) {
            if ($approvedRestaurantCount < (int) $minimumRestaurants) {
                break;
            }

            $percentage = (float) $tierPercentage;
        }

        return round($percentage, 2);
    }

    /**
     * @return array{minimum_restaurants: int, percentage: float}|null
     */
    public function nextCommissionTier(?int $approvedRestaurantCount = null): ?array
    {
        $approvedRestaurantCount ??= $this->approvedRestaurantCount();
        $tiers = config('agent.commission_tiers', [0 => 15, 35 => 20, 50 => 30]);
        ksort($tiers, SORT_NUMERIC);

        foreach ($tiers as $minimumRestaurants => $tierPercentage) {
            if ($approvedRestaurantCount < (int) $minimumRestaurants) {
                return [
                    'minimum_restaurants' => (int) $minimumRestaurants,
                    'percentage' => (float) $tierPercentage,
                ];
            }
        }

        return null;
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AgentCommission::class);
    }
}
