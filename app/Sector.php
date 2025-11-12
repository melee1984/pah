<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    protected $table = 'sector';
	protected $fillable = array('name', 'active','is_featured', 'img');
	public $timestamps = false;


	public function merchants() 
    {
        return $this->hasMany('App\Partner','partner_id');   
    }

}
