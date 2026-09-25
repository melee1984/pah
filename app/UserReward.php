<?php

namespace App;

use App\Model\Orders\Orders;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserReward extends Model
{
    public const STATUS_EARNED = 'earned';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'user_id',
        'order_id',
        'points',
        'order_amount',
        'php_per_point',
        'status',
        'earned_at',
        'reversed_at',
        'reversal_reason',
    ];

    protected $casts = [
        'points' => 'integer',
        'order_amount' => 'decimal:2',
        'php_per_point' => 'decimal:2',
        'earned_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function scopeEarned(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_EARNED);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }
}
