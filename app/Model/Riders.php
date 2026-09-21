<?php

namespace App\Model;

use App\Model\Rider\RiderApiLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Riders extends Model
{
	protected $table = 'rider';
	protected $fillable = array('name','date_join', 'licence_no', 'mobile', 'is_active');
	public $timestamps = true;

	protected $hidden = array('created_at', 'updated_at');
	
	public function scopeActive($query) {
		return $query->whereActive(1);
	}

	  public function user() {
        return $this->hasOne('App\User','id','user_id');     
    }

    public function location(): HasOne
    {
        return $this->hasOne(RiderApiLocation::class, 'rider_id')
            ->ofMany(['recorded_at' => 'max', 'id' => 'max']);
    }

}
