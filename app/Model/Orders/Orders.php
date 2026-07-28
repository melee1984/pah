<?php

namespace App\Model\Orders;

use Illuminate\Database\Eloquent\Model;

use DB;
use App\Model\Orders\OrderProcess;

class Orders extends Model
{
    protected $table = 'order';
	protected $fillable = array('user_id', 'order_no', 'cart_id', 'submitted_at', 'partner_id','status_id');
	public $timestamps = true;

    protected $dates = [
        'submitted_at',
        'delivered_at',
        'updated_at',
        'created_at'
    ];

	/**
	 * [cart description]
	 * @return [type] [description]
	 */
    public function cart() 
    {
         return $this->hasOne('App\Model\Cart', 'id', 'cart_id')->with('partner');
    }

    /**
	 * [cart description]
	 * @return [type] [description]
	 */
    public function orderprocess() 
    {
         return $this->hasMany('App\Model\Orders\OrderProcess','order_id', 'id');
    }

    /**
     * [status description]
     * @return [type] [description]
     */
    public function status() 
    {
         return $this->hasOne('App\LibraryStatus','id', 'status_id');
    }

    public function getActionLogs() {
         $query = DB::table('library_status as status')
            ->select('status.id', 'status.title', 'status.sorting', 'status.description')
            ->selectSub(function ($query) {
                $query->from('order_process as process')
                    ->selectRaw('COUNT(process.id)')
                    ->where('process.order_id', $this->id)
                    ->whereColumn('process.status_id', 'status.id');
            }, 'st')
            ->selectSub(function ($query) {
                $query->from('order_process as process')
                    ->select('process.created_at')
                    ->where('process.order_id', $this->id)
                    ->whereColumn('process.status_id', 'status.id')
                    ->orderBy('process.created_at', 'asc')
                    ->limit(1);
            }, 'datecreated')
            ->orderBy('status.sorting', 'asc');

         if ((int) $this->status_id === 8) {
            $query->whereIn('status.id', [1, 8]);
         } else {
            $query->where('status.id', '!=', 8);
         }

         $rs = $query->get();

         foreach($rs as $list) {
            if ($list->datecreated !="") {
                $list->time_accepted = date('G:ia',  strtotime($list->datecreated));    
            }
         }

         return $rs;
                

    }
    /**
     * [status description]
     * @return [type] [description]
     */
    public function rider() 
    {
         return $this->hasOne('App\Model\Riders','id', 'rider_id');
    }

     public function partner() {
         return $this->hasOne('App\Partners', 'id', 'partner_id');   
    }   

     public function user() 
    {
         return $this->hasOne('App\User', 'id', 'user_id');
    }

}
