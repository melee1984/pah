<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductItemDetails extends Model
{
    protected $table = 'product_item_details';
	protected $fillable = array('product_header_id', 'title', 'price', 'price_comm', 'markdown_price', 'active');
	public $timestamps = true;

    public function productheader()
    {
        return $this->belongsTo('App\ProductItemHeader', 'product_header_id', 'id');
    }
    public function getPrice() {

        if ($this->productheader->product->user->merchant->addup) {
            $price = number_format((float)$this->price + (float)$this->price_comm,2);    
        }
        else {
            $price = number_format((float)$this->price,2);
        }
        
        return $price;
    }
    public function getPriceComm() {
        $price = number_format((float)$this->price_comm,2);
        return $price;
    }

}
