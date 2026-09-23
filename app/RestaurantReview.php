<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RestaurantReview extends Model
{
    protected $fillable = [
        'user_id',
        'partner_id',
        'partner_location_id',
        'rating',
        'comment',
        'active',
    ];

    protected $casts = [
        'rating' => 'integer',
        'active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partners::class);
    }

    public function location()
    {
        return $this->belongsTo(PartnerLocation::class, 'partner_location_id');
    }
}
