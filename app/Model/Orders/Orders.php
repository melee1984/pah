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

            if ($this->status->id == 8) {
                 $sql = "select  pah_db.library_status.id, pah_db.library_status.title, pah_db.library_status.sorting, pah_db.library_status.description, 

                        (select 
                          count(pah_db.order_process.id)
                        from 
                            pah_db.order,
                            pah_db.order_process
                        where 
                            pah_db.order.id = pah_db.order_process.order_id
                            and pah_db.order.cart_id = ? 
                            and pah_db.order_process.status_id = pah_db.library_status.id limit 0,1) 
                            as st,
                        (select 
                          pah_db.order_process.created_at
                        from 
                            pah_db.order,
                            pah_db.order_process
                        where 
                            pah_db.order.id = pah_db.order_process.order_id
                            and pah_db.order.cart_id = ?
                            and pah_db.order_process.status_id = pah_db.library_status.id
                            limit 0,1) as datecreated
                        from 
                            pah_db.library_status 
                        where 
                             pah_db.library_status.id in (1, 8)
                        order by 
                            pah_db.library_status.sorting asc ";
                $rs = DB::select($sql, array($this->cart_id, $this->cart_id));
            }
            else {
                  $sql = "select  pah_db.library_status.id, pah_db.library_status.title, pah_db.library_status.sorting, pah_db.library_status.description, 
                        (select 
                          count(pah_db.order_process.id)
                        from 
                            pah_db.order,
                            pah_db.order_process
                        where 
                            pah_db.order.id = pah_db.order_process.order_id
                            and pah_db.order.cart_id = ? 
                            and pah_db.order_process.status_id = pah_db.library_status.id limit 0,1) 
                            as st,
                        (select 
                          pah_db.order_process.created_at
                        from 
                            pah_db.order,
                            pah_db.order_process
                        where 
                            pah_db.order.id = pah_db.order_process.order_id
                            and pah_db.order.cart_id = ?
                            and pah_db.order_process.status_id = pah_db.library_status.id
                            limit 0,1) as datecreated
                        from 
                            pah_db.library_status 
                        where 
                             pah_db.library_status.id != 8
                        order by 
                            pah_db.library_status.sorting asc ";

                 $rs = DB::select($sql, array($this->cart_id, $this->cart_id));

            }
                 
            // if ($this->status->id == 8) {

            //      $sql = "select  p1.library_status.id, p1.library_status.title, p1.library_status.sorting, p1.library_status.description, 

            //             (select 
            //               count(p1.order_process.id)
            //             from 
            //                 p1.order,
            //                 p1.order_process
            //             where 
            //                 p1.order.id = p1.order_process.order_id
            //                 and p1.order.cart_id = ? 
            //                 and p1.order_process.status_id = p1.library_status.id limit 0,1) 
            //                 as st,

            //             (select 
            //               p1.order_process.created_at
            //             from 
            //                 p1.order,
            //                 p1.order_process
            //             where 
            //                 p1.order.id = p1.order_process.order_id
            //                 and p1.order.cart_id = ?
            //                 and p1.order_process.status_id = p1.library_status.id
            //                 limit 0,1) as datecreated

            //             from 
            //                 p1.library_status 
            //             where 
            //                  p1.library_status.id in (1, 8)
            //             order by 
            //                 p1.library_status.sorting asc ";

            //     $rs = DB::select($sql, array($this->cart_id, $this->cart_id));

            // }
            // else {

            //      $sql = "select  p1.library_status.id, p1.library_status.title, p1.library_status.sorting, p1.library_status.description, 

            //             (select 
            //               count(p1.order_process.id)
            //             from 
            //                 p1.order,
            //                 p1.order_process
            //             where 
            //                 p1.order.id = p1.order_process.order_id
            //                 and p1.order.cart_id = ? 
            //                 and p1.order_process.status_id = p1.library_status.id limit 0,1) 
            //                 as st,
            //             (select 
            //               p1.order_process.created_at
            //             from 
            //                 p1.order,
            //                 p1.order_process
            //             where 
            //                 p1.order.id = p1.order_process.order_id
            //                 and p1.order.cart_id = ?
            //                 and p1.order_process.status_id = p1.library_status.id
            //                 limit 0,1) as datecreated
            //             from 
            //                 p1.library_status 
            //             where 
            //                  p1.library_status.id != 8
            //             order by 
            //                 p1.library_status.sorting asc ";

            //      $rs = DB::select($sql, array($this->cart_id, $this->cart_id));

            // }

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
