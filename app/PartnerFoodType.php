<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PartnerFoodType extends Model
{
    protected $table = 'partner_food_type';
	public $timestamps = true;
    protected $fillable = array('partner_id',
								'name');
    
}
