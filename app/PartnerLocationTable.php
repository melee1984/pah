<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PartnerLocationTable extends Model
{
    protected $table = 'partner_location_tables';

    protected $fillable = [
        'partner_location_id',
        'name',
        'capacity',
        'active',
        'is_available',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'active' => 'boolean',
        'is_available' => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(PartnerLocation::class, 'partner_location_id');
    }
}
