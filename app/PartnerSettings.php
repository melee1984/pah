<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PartnerSettings extends Model
{
    protected $table = 'partner_settings';
	protected $fillable = array('partner_id', 'is_visible');
	public $timestamps = true;


}
