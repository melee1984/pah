<?php

namespace App\Model\Rider;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderApiLocation extends Model
{
    protected $table = 'rider_api_locations';

    protected $fillable = [
        'rider_id',
        'delivery_reference',
        'latitude',
        'longitude',
        'accuracy_meters',
        'heading',
        'speed_mps',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy_meters' => 'decimal:2',
            'heading' => 'decimal:2',
            'speed_mps' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class, 'rider_id');
    }
}
