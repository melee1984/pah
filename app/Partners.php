<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Partners extends Model
{
    protected $fillable = array(
                                'user_id',
                                'restaurant_name',
								'email',
								'mobile',
                                'telephone',
								'address',
                                'description',
								'city',
                                'active',
                                'longtitude',
                                'latitude',
                                'slug',
                                'search_string',
                                'facebook',
                                'account_type_id',
                                'percentage',
                                'addup',
                                'is_pre_order'
                            );
    
	public $timestamps = true;

	protected $casts = [
        'archive_at' => 'datetime',
        'verified_at' => 'datetime',
        'sms_verified' => 'datetime',
    ];
    
    protected $hidden = ['verified_at', 'updated_at', 'created_at' , 'archive_at', 'deleted_at'];

    /**
     * [merchant description]
     * @return [type] [description]
     */
    public function user()
    {
        return $this->hasOne('App\User')
                ->with('locations');
    }

    public function products() {
        return $this->hasMany('App\Products', 'user_id', 'user_id');
    }

    public function category() {
        return $this->hasMany('App\Category', 'user_id', 'user_id');
    }

    public function foodType() {
        return $this->hasMany('App\PartnerFoodType', 'partner_id', 'id');
    }
    /**
     * [category description]
     * @return [type] [description]
     */
    public function locations() 
    {
        return $this->hasMany('App\PartnerLocation','partner_id');   
    }

    public function budget()
    {
        return $this->belongsTo('App\LibraryBudget');
    }

    public function accoutType()
    {
        return $this->belongsTo('App\AccountType', 'account_type_id' ,'id');
    }

    /**
     * [category description]
     * @return [type] [description]
     */
    public function location() 
    {
        return $this->hasOne('App\PartnerLocation','partner_id')->latest();   
    }

    public static function imgCheck($merchant, $for='logo') {

        if ($for=='logo')  {
            if ($merchant->img !="") {
                $merchant->img = asset('uploads/user/'.$merchant->id."/".$merchant->img) ;
            }
            else {
                $merchant->img = asset('uploads/no-img.png') ;   
            }
        }
        else if($for=='banner') {

            if ($merchant->banner !="") {
                $merchant->banner = asset('uploads/user/'.$merchant->id."/banner/".$merchant->banner) ;
            }

        }

        return $merchant;
    }
    /**
     * [image description]
     * @param  [type] $query [description]
     * @return [type]        [description]
     */
    public function image($for='logo') {

         if ($for=='logo') {
            if ($this->img !="") {
             return asset('uploads/user/'.$this->id."/".$this->img) ;    
            }
            else {
                return asset('uploads/no-img.png') ;   
            }
         }
         else {

            if ($this->banner !="") {
             return asset('uploads/user/'.$this->id."/banner/".$this->banner) ;    
            }
            else {
                return asset('uploads/no-img.png') ;   
            }

         }

    }

     public static function imageCacheUrl($merchant, $for='logo') {

        if ($for == 'logo') {

            if ($merchant->img !="") {
                $merchant->img = route('cacheImage')."?for=logo&name=".$merchant->id."/".$merchant->img; 
            }
            else {
                $merchant->img = asset('uploads/no-img.png') ;   
            }

        }
        else if($for == 'banner') {

             if ($merchant->banner !="") {
                $merchant->banner = route('cacheImage')."?for=banner&name=".$merchant->id."/banner/".$merchant->banner; 
            }
            else {
                $merchant->banner = asset('uploads/no-img.png') ;   
            }

        }
        
        return $merchant;

    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function scopeActiveRestaurants($query) 
    {
         return  $query->whereActive(1)
                    ->with('foodType')
                    ->whereNotNull('verified_at');
                  
    }

    /**
     * Mobile use  to resize images 
     * @param  [type] $merchant [description]
     * @return [type]           [description]
     */
    public static function imageResize($merchant, $for='logo', $isDesktop = true) {

        if ($isDesktop) {

            if ($for == 'logo') {
                if ($merchant->img !="") {
                    $merchant->img = route('image-resize')."?for=logo&w=400&name=".$merchant->id."/".$merchant->img; 
                }
                else {
                    $merchant->img = asset('uploads/no-img.png') ;   
                }

            }
            elseif($for == 'banner') {

                if ($merchant->banner !="") {
                    $merchant->banner = route('image-resize')."?for=banner&w=400=".$merchant->id."/banner/".$merchant->banner; 
                }
                else {
                    $merchant->banner = asset('uploads/no-img.png') ;   
                }

            }
            elseif($for == 'logo-banner-size') { 

                if ($merchant->img !="") {
                    $merchant->img = route('image-resize')."?for=logo&w=250&h=250&name=".$merchant->id."/".$merchant->img; 
                }
                else {
                    $merchant->img = asset('uploads/no-img.png') ;   
                }
            }

        }
        else {
            if ($for == 'logo') {
                if ($merchant->img !="") {
                    $merchant->img = route('image-resize')."?for=thumb&w=200&name=".$merchant->id."/".$merchant->img; 
                }
                else {
                    $merchant->img = asset('uploads/no-img.png') ;   
                }

            }
            elseif($for == 'banner') {

                if ($merchant->banner !="") {
                    $merchant->banner = route('image-resize')."?for=banner&w=400&h=270=".$merchant->id."/banner/".$merchant->banner; 
                }
                else {
                    $merchant->banner = asset('uploads/no-img.png') ;   
                }

            }
            elseif($for == 'logo-banner-size') { 

                if ($merchant->img !="") {
                    $merchant->img = route('image-resize')."?for=logo&w=400&h=270&name=".$merchant->id."/".$merchant->img; 
                }
                else {
                    $merchant->img = asset('uploads/no-img.png') ;   
                }
            }

        }
        return $merchant;
    }

    public static function imageResizeThumb($obj, $partner_id, $isDesktop = true) {

       if ($isDesktop) {
            if ($obj->img!="") {
                return route('image-resize')."?for=thumb&w=500&name=".$partner_id."/".$obj->img; 
            }
            else {
                return  asset('uploads/no-img.png') ;   
            }
       }
       else {
            if ($obj->img!="") {
                return route('image-resize')."?for=thumb&w=400&h=270&name=".$partner_id."/".$obj->img; 
            }
            else {
               return  asset('uploads/no-img.png') ;   
            }
       }
        
    }

    public static function imgBanner($merchant) {
        return route('image-resize')."?for=banner&w=400&h=100&name=".$merchant->id."/banner/".$merchant->banner; 
    }

    /**
     * [merchant description]
     * @return [type] [description]
     */
    public function partner_user()
    {
        return $this->hasOne('App\User','id', 'user_id');
    }    

}
