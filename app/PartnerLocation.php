<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PartnerLocation extends Model
{
    protected $table = 'partner_location';
	protected $fillable = array('partner_id', 'address_1', 'city','telephone', 'mobile');
	public $timestamps = true;

	public function partner()
    {
        return $this->belongsTo('App\Partner', 'id', 'partner_id');
    }
}
