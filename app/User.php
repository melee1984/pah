<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;
use Auth;


class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname','lastname', 'email', 'mobile', 'password','ip_address', 'api_token', 'description', 'telephone','account_type_id', 'provider', 'provider_id', 'avatar', 'device_token','device_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'verified_at', 'ip_address','updated_at', 'id', 'sms_verified_at', 'sms_code', 'email_verified_at', 'api_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_of_date' => 'datetime',
        'sms_verified' => 'datetime',
    ];

     /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->firstname . " " . $this->lastname;
    }
     /**
     * Validate User if has Admin Roles 
     * @return boolean
     */
    public function isMerchantVerified() 
    {        
        if (Auth::User()->merchant->verified_at != null) {
            return true;
        }
        
        return false;
    }  
    /**
     * Validate User if has Admin Roles 
     * @return boolean
     */
    public function isAdmin(): bool
    {        
        return $this->hasRole('admin');
    }   

    /**
     * Adding Roles for each account note // $rs->assignRole('partner')
     * 
     * @return boolean
     */
    public function isMerchant() 
    {
        return Auth::User()->hasRole('partner');
    }
    
    /**
     * [merchant description]
     * @return [type] [description]
     */
    public function merchant()
    {
        return $this->hasOne('App\Partners')->with('locations');
    }

    /**
     * [category description]
     * @return [type] [description]
     */
    public function categories() 
    {
        return $this->hasMany('App\Category');   
    }

    /**
     * [products description]
     * @return [type] [description]
     */
    public function products() 
    {
        return $this->hasMany('App\Products')->with('category');

    }    

    public function addresses() {
        return $this->hasMany('App\Model\User\UserAddress');     
    }   
    

    public function rider() {
        return $this->hasOne('App\Model\Rider\Rider');     
    }

     public function pickupaddress() {
        return $this->hasMany('App\Model\Bookings\BookingPickupInfo');     
    } 

     public function dropoffaddress() {
        return $this->hasMany('App\Model\Bookings\BookingDropoffInfo');     
    } 
  
}
