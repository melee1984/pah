<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductItemHeader extends Model
{
    protected $table = 'product_item_header';
	protected $fillable = array('product_id', 'banner_img', 'title', 'active', 'sorting','is_required', 'is_multiple');
	public $timestamps = true;


	public function product()
    {
        return $this->hasOne('App\products', 'id', 'product_id');
    }

    public function product_details()
    {
        return $this->hasMany('App\ProductItemDetails', 'product_header_id', 'id');
    }

    public static function imgCheck($obj, $size = 1) 
    {
        if ($obj) {
            // Get the Image of the Addons 
            if ($obj->img !="") {
                if (!$size) {
                    $obj->img = asset('uploads/'.$obj->id."/thumb/".$obj->img) ;    
                }
                else {
                    $obj->img = asset('uploads/'.$obj->id."/".$obj->img) ;
                 }
            }
            else {
                // Otherwise get the image of the product img 
                if ($obj->product->img !="") {
                    if (!$size) {
                        $obj->img = asset('uploads/'.$obj->product->id."/thumb/".$obj->product->img) ;    
                    }
                    else {
                        $obj->img = asset('uploads/'.$obj->product->id."/".$obj->product->img) ;
                     }
                }
                else {
                    
                    $obj->img = asset('uploads/no-img.png') ;   
                }

            }
        }
       
        return $obj;
    }
   
}
