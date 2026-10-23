<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantBranchUser extends Model
{
    protected $fillable = [
        'user_id',
        'partner_id',
        'partner_location_id',
        'created_by_user_id',
        'active',
    ];

    protected $casts = [
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

    public function branch()
    {
        return $this->belongsTo(PartnerLocation::class, 'partner_location_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
