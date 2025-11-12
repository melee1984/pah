<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = 'cart_details';
	protected $fillable = array('cart_id', 'item_id','qty', 'price', 'variance_total', 'variance_content', 'is_not_available', 'instruction', 'price_comm_total', 'variance_total_comm_total', 'discount_amount');
	public $timestamps = true;


	public function item()
    {
        return $this->hasOne('App\Products', 'id', 'item_id');
    }
	
}
