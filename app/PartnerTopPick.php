<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PartnerTopPick extends Model
{
    protected $table = 'partner_top_picks';

    protected $fillable = [
        'partner_id',
        'active',
        'expiration_date',
    ];

    protected $casts = [
        'active' => 'boolean',
        'expiration_date' => 'datetime',
    ];

    public function partner()
    {
        return $this->belongsTo(Partners::class, 'partner_id');
    }
}
