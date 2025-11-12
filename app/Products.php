<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use URL;

class Products extends Model
{	
	use SoftDeletes;

    protected $table = 'products';
	protected $fillable = array('user_id', 'title', 'short_description', 'description', 'slug', 'price', 'active','category_id', 'price_comm', 'markdown_price_comm');
	public $timestamps = true; 
    // protected $visible = ['id','title', 'description', 'slug', 'price', 'markdown_price', 'tags', 'imgname', 'variants'];


	/**
	 * Get merchant Information 
	 * @return User Object 
	 */
	public function user()
    {
        return $this->hasOne('App\User','id', 'user_id');
    }

    public function variants() 
    {
         return $this->hasMany('App\ProductItemHeader','product_id','id')
            ->with('product_details')->orderBy('sorting', 'asc');
    }
    /**
	 * Get merchant Information 
	 * @return User Object 
	 */
	public function category()
    {
        return $this->hasOne('App\Category','id','category_id');
    }

    public static function scopeProductByActive($query) 
    {   
        return  $query->whereStatus(true)
                        ->whereDeleteAt(null)
                        ->orderBy('created_at','asc');
    }
    public static function scopeProductById($query) 
    {   
        return  $query->whereStatus(true)
                        ->whereDeleteAt(null)
                        ->with('variants')
                        ->orderBy('created_at','asc');
    }

    public static function productImageCheck($product, $size = 1) {

        if ($product->img !="") {
            if (!$size) {
                // $product->img_large = asset('uploads/'.$product->id."/".$product->img) ;    
                $product->imgname = URL::to('imager?name='.$product->id."/".$product->img);
            }
            else {
                $product->imgname = URL::to('imager?name='.$product->id."/".$product->img);
            }
        }
        else {
            $product->imgname = URL::to('imager?name=no-img.png');
        }
        return $product;
        
    }
    public static function amountConvertion($product) {

        $product->price = str_replace(",", "", $product->price);
        $display_price =  "₱" .number_format($product->price,2);

        if ($product->markdown_price) {
            $product->markdown_price = str_replace(",", "", $product->markdown_price);
            $product->markdown_price =  "₱" .number_format($product->markdown_price,2);    
        }
        $product->price = $display_price;

        return $product;
    }

    public function getPriceDisplay($saveDb = false) {

        if ($this->user->merchant->addup) {
            $this->price = number_format((float)$this->price + $this->price_comm,2);        
        }
        else {
            $this->price = number_format((float)$this->price ,2);
        }
        
        if ($this->markdown_price!="") {
             if ($this->user->merchant->addup) {
                $this->markdown_price = number_format((float)$this->markdown_price + (float)$this->markdown_price_comm,2);    
            }
            else {
                $this->markdown_price = number_format((float)$this->markdown_price ,2);
            }
        }
        
        // CHeck if has some pahatud discount over
        if ($this->user->merchant->pahatud_comm_discount) {

            // echo $this->price ;
            // echo "<br>";
            // echo $this->price_comm;

            // die();

            // $this->markdown_price = number_format((float)$this->price - $this->price_comm,2);   
            $price = str_replace(',', '', $this->price);
            $this->markdown_price = number_format($price - $this->price_comm,2);
        }
        else if ($this->user->merchant->pahatud_discount!="") {
            $this->markdown_price = number_format((float)$this->price - $this->user->merchant->pahatud_discount,2);   
        }

    }


    public function getPrice($saveDb = false) {

        if ($this->user->merchant->addup) {
            $price = number_format((float)$this->price + $this->price_comm,2);        
        }
        else {
            $price = number_format((float)$this->price ,2);
        }
        
        if ($this->markdown_price!="") {
             if ($this->user->merchant->addup) {
                $price = number_format((float)$this->markdown_price + (float)$this->markdown_price_comm,2);    
            }
            else {
                $price = number_format((float)$this->markdown_price ,2);
            }
        }
        
        // Check if has some pahatud discount over
        // if ($this->user->merchant->pahatud_comm_discount) {
        //     $price = number_format($price - $this->price_comm,2);   
        // }
        // else if ($this->user->merchant->pahatud_discount!="") {
        //     $price = number_format($price - $this->user->merchant->pahatud_discount,2);   
        // }


        if ($saveDb) {
           $price = str_replace(",","", $price);
        }
        
        return $price;
    }

    public function getPriceComm() {

        $price = number_format((float)$this->price_comm,2);
        
        if ($this->markdown_price!="") {
            $price = number_format((float)$this->markdown_price_comm,2);    
        }

         // CHeck if has some pahatud discount over
        if ($this->user->merchant->pahatud_comm_discount) {
            $price = 0;
        }
        else if ($this->user->merchant->pahatud_discount!="") {
            
        }

        return $price;
    }

    public function getDiscountAmount() {

        $price = 0;
        
        // Check if has some pahatud discount over
        if ($this->user->merchant->pahatud_comm_discount) {
            $price = number_format($this->price_comm,2);   
        }
        else if ($this->user->merchant->pahatud_discount!="") {
            $price = number_format($this->user->merchant->pahatud_discount,2);   
        }

        return $price;

    }

    
}
