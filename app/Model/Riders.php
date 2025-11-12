<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Riders extends Model
{
	protected $table = 'rider';
	protected $fillable = array('name','date_join', 'licence_no', 'mobile');
	public $timestamps = true;

	protected $hidden = array('created_at', 'updated_at');
	
	public function scopeActive($query) {
		return $query->whereActive(1);
	}

	  public function user() {
        return $this->hasOne('App\User','id','user_id');     
    }

}
