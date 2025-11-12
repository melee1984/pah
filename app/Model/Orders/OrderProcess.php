<?php

namespace App\Model\Orders;

use Illuminate\Database\Eloquent\Model;

class OrderProcess extends Model
{
  	protected $table = 'order_process';
	protected $fillable = array('status_id', 'order_id', 'user_id');
	public $timestamps = true;

	/**
	 * [cart description]
	 * @return [type] [description]
	 */
    public function order() 
    {
         return $this->hasMany('App\Model\Orders\Orders','order_id', 'id');
    }
    /**
     * Validate User if has Admin Roles 
     * @return boolean
     */
    public function status() 
    {
         return $this->hasOne('App\LibraryStatus','id', 'status_id');
    }

}
