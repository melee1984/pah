<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
 	protected $table = 'partner_location';
	protected $fillable = array('partner_id','address_1', 'address_2', 'city','zipcode', 'telephone','mobile', 'active');
	public $timestamps = true;
}
