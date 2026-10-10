<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantApplication extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DECLINED = 'declined';

    public const SERVICES = [
        'dine_in' => 'Dine-in',
        'pickup' => 'Pickup',
        'delivery' => 'Delivery',
    ];

    protected $fillable = [
        'business_name',
        'registered_business_name',
        'owner_name',
        'email',
        'mobile',
        'telephone',
        'address',
        'city',
        'business_structure',
        'cuisine',
        'branch_count',
        'services',
        'website',
        'business_description',
        'commission_percentage',
        'status',
        'review_message',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'services' => 'array',
            'commission_percentage' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function serviceLabels(): array
    {
        return collect($this->services ?? [])
            ->map(fn (string $service) => self::SERVICES[$service] ?? str($service)->headline()->toString())
            ->all();
    }
}
