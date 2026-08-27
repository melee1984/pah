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
}
