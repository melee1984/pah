<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
   	protected $table = 'coupon';
	protected $fillable = array('coupon','valid_at', 'limit');
	public $timestamps = true;
	
}
