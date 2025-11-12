<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PartnerInquiry extends Model
{	
	protected $table = 'partner_inquiry';
	public $timestamps = true;
    protected $fillable = array('restaurant_name',
								'email',
								'contact_no',
								'address',
								'city');
    
}
