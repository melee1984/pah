<?php

namespace App\Model\User;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
   	protected $table = 'user_address';
	protected $fillable = array('user_id', 'active', 'address_1', 'mobile', 'landmark', 'lat', 'long', 'default' ,'title');
	public $timestamps = true;

}
