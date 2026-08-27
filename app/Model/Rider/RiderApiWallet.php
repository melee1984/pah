<?php

namespace App\Model\Rider;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderApiWallet extends Model
{
    protected $table = 'rider_api_wallets';

    protected $fillable = [
        'rider_id',
        'credit_amount',
        'credit_points',
    ];

    protected function casts(): array
    {
        return [
            'credit_amount' => 'decimal:2',
            'credit_points' => 'integer',
        ];
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class, 'rider_id');
    }
}
