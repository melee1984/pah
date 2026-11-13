<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductLocationAvailability extends Model
{
    protected $table = 'product_location_availability';

    protected $fillable = [
        'product_id',
        'partner_location_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
