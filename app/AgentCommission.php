<?php

namespace App;

use App\Model\Orders\Orders;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCommission extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'order_id',
        'restaurant_id',
        'agent_id',
        'order_amount',
        'commission_percentage',
        'commission_amount',
        'status',
        'qualified_at',
        'reversed_at',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'order_amount' => 'decimal:2',
            'commission_percentage' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'qualified_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Partners::class, 'restaurant_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function scopeEarned($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_PAID,
        ]);
    }
}
