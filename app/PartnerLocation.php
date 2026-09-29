<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PartnerLocation extends Model
{
    protected $table = 'partner_location';
	protected $fillable = array(
        'partner_id',
        'address_1',
        'address_2',
        'city',
        'zip_code',
        'telephone',
        'mobile',
        'device_token',
        'latitude',
        'longtitude',
        'active',
    );
	public $timestamps = true;

	public function partner()
    {
        return $this->belongsTo(Partners::class, 'partner_id');
    }

    public function diningTables()
    {
        return $this->hasMany(PartnerLocationTable::class, 'partner_location_id');
    }

    public function checkoutOptions()
    {
        return $this->hasMany(PartnerLocationCheckoutOption::class, 'partner_location_id');
    }
}
