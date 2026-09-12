<?php

namespace App\Model\Rider;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rider extends Model
{
    protected $table = 'rider';

    public $timestamps = true;

    protected $casts = [
        'active' => 'boolean',
        'is_active' => 'boolean',
        'date_join' => 'datetime',
    ];

    public function wallet(): HasOne
    {
        return $this->hasOne(RiderApiWallet::class, 'rider_id');
    }
}
