<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
   	protected $table = 'account_type';
	protected $fillable = array('title');
	public $timestamps = false;
}
