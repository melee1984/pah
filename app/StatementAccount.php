<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StatementAccount extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ISSUED_LEGACY = 'issued';

    protected $fillable = [
        'reference',
        'partner_id',
        'created_by',
        'status',
        'order_count',
        'period_start',
        'period_end',
        'subtotal_amount',
        'convenience_fee_amount',
        'vat_amount',
        'delivery_fee_amount',
        'discount_amount',
        'total_amount',
        'commission_amount',
        'merchant_net_amount',
        'notes',
        'issued_at',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'issued_at' => 'datetime',
        'subtotal_amount' => 'decimal:2',
        'convenience_fee_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'delivery_fee_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'merchant_net_amount' => 'decimal:2',
    ];

    public function partner()
    {
        return $this->belongsTo(Partners::class, 'partner_id');
    }

    public function items()
    {
        return $this->hasMany(StatementAccountItem::class)->orderBy('completed_at');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return in_array($this->status, [self::STATUS_PUBLISHED, self::STATUS_ISSUED_LEGACY], true);
    }
}
