<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_method';
	protected $fillable = array('title','active');
	public $timestamps = true;
	

	public function scopeActive($query) {
		return $query->whereActive(1)->get();
	}
}
