<?php

namespace App\Model\Orders;

use Illuminate\Database\Eloquent\Model;

use DB;
use App\Model\Orders\OrderProcess;
use App\Model\Bookings\BookingStatus;
use App\Model\Rider\RiderDeclineOrder;

class Orders extends Model
{
    protected $table = 'order';
	protected $fillable = array('user_id', 'order_no', 'cart_id', 'submitted_at', 'partner_id', 'rider_id', 'store_accepted_at', 'accepted_by_rider_at', 'accepted_at', 'delivered_at', 'updated_at', 'created_at', 'order_status_id', 'booking_status_id');
	public $timestamps = true;

    protected $dates = [
        'submitted_at',
        'store_accepted_at',
        'accepted_by_rider_at',
        'accepted_at',
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
         return $this->hasOne(BookingStatus::class, 'id', 'status_id');
    }

    // get should be the latest status of the order
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

         if ((int) $this->status_id === 7) {
            $query->whereIn('status.id', [1, 7]);
         } else {
            $query->where('status.id', '!=', 7);
         }

         $rs = $query->get();

         foreach($rs as $list) {
            if ($list->datecreated !="") {
                $list->time_accepted = date('G:ia',  strtotime($list->datecreated));    
            }
         }

         return $rs;
    }

    public function getRiderAction()
    {
        $action = null;

        if ((int) $this->status_id === self::STATUS_ORDER_PLACED) {
            $action = [
                'label' => 'Accept Booking',
                'action' => 'accept',
            ];
        } elseif ((int) $this->status_id === self::STATUS_ORDER_ACCEPTED || (int) $this->status_id === self::STATUS_PROCESSING) {
            $action = [
                'label' => 'Ready For Pickup',
                'action' => 'ready-for-pickup',
            ];
        }
        elseif ((int) $this->status_id === self::STATUS_READY_FOR_PICKUP) {
            $action = [
                'label' => 'Pickup Order',
                'action' => 'pickup-order',
            ];
        }
        elseif ((int) $this->status_id === self::STATUS_RIDER_PICKED_UP) {
            $action = [
                'label' => 'Delivered Order',
                'action' => 'delivered-order',
            ];
        }


        return $action;
    }

    // order action for merchant 
    public function getAction()
    {
        return match ((int) $this->status_id) {
            self::STATUS_ORDER_PLACED => [
                'label' => 'Pending',
                'button' => [
                    'label' => 'Accept Order',
                    'action' => 'accept',
                ],
                'cancel' => [
                    'label' => 'Cancel Order',
                    'action' => 'cancel',
                ],
                'send_to_rider' => false,
            ],
            self::STATUS_ORDER_ACCEPTED, self::STATUS_PROCESSING => [
                'label' => 'Order Processing',
                'button' => [
                    'label' => 'Ready For Pickup',
                    'action' => 'ready-for-pickup',
                ],
                'cancel' => [
                    'label' => 'Cancel Order',
                    'action' => 'cancel',
                ],
                'send_to_rider' => true,
            ],
            self::STATUS_READY_FOR_PICKUP => [
                'label' => 'Waiting for Rider to Pickup',
                'button' => null,
                'cancel' => [
                    'label' => 'Cancel Order',
                    'action' => 'cancel',
                ],
                'send_to_rider' => false,
            ],
            self::STATUS_DELIVERED => [
                'label' => 'Order Delivered',
                'button' => null,
                'cancel' => null,
                'send_to_rider' => false,
            ],
            self::STATUS_CANCELLED => [
                'label' => 'Cancelled Order',
                'button' => null,
                'cancel' => null,
                'send_to_rider' => false,
            ],
            default => [
                'label' => $this->status?->title ?? $this->status?->description,
                'button' => null,
                'cancel' => null,
                'send_to_rider' => false,
            ],
        };
    }

    /**
     * [status description]
     * @return [type] [description]
     */
    public function rider() 
    {
         return $this->hasOne('App\Model\Riders','id', 'rider_id');
    }

    public function riderDeclines()
    {
         return $this->hasMany(RiderDeclineOrder::class, 'order_id');
    }

     public function partner() {
         return $this->hasOne('App\Partners', 'id', 'partner_id');   
    }   

     public function user() 
    {
         return $this->hasOne('App\User', 'id', 'user_id');
    }

}
