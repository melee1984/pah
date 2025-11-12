<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CartUserAddress extends Model
{
    protected $table = 'cart_user_address';
	protected $fillable = array('cart_id', 'user_id', 'address_1', 'address_2', 'zip_code', 'mobile', 'landmark', 'country_id', 'province_id', 'city_id', 'barangay_id', 'lat', 'long',);
	public $timestamps = true;

}
