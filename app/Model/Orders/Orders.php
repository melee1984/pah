<?php

namespace App\Model\Orders;

use Illuminate\Database\Eloquent\Model;

use DB;
use App\Model\Orders\OrderProcess;
use App\Model\Bookings\BookingStatus;
use App\Model\Rider\RiderDeclineOrder;
use App\LibraryStatus;

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
    public function orderStatus() 
    {
         return $this->hasOne(LibraryStatus::class, 'id', 'order_status_id');
    }

    public function status() 
    {
         return $this->hasOne(BookingStatus::class, 'id', 'booking_status_id');
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
        // if the order is cancelled, we will show the cancelled status as well
         if ((int) $this->order_status_id === LibraryStatus::STATUS_CANCELLED) {
            $query->whereIn('status.id', [LibraryStatus::STATUS_CANCELLED]);
         } else {
            $query->where('status.id', '!=', LibraryStatus::STATUS_CANCELLED);
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

        if ((int) $this->booking_status_id === BookingStatus::STATUS_BOOKING_PLACED) {
            $action = [
                'label' => 'Accept Booking',
                'action' => 'accept',
            ];
        } elseif ((int) $this->booking_status_id === BookingStatus::STATUS_BOOKING_ACCEPTED || (int) $this->booking_status_id === BookingStatus::STATUS_BOOKING_PROCESSING) {
            $action = [
                'label' => 'Picked Order',
                'action' => 'picked-order',
            ];
        }
        elseif ((int) $this->booking_status_id === BookingStatus::STATUS_BOOKING_READY_FOR_PICKUP) {
            $action = [
                'label' => 'Start Route to Customer',
                'action' => 'start-customer-route',
            ];
        }
        elseif ((int) $this->booking_status_id === BookingStatus::STATUS_BOOKING_RIDER_PICKED_UP) {
            $action = [
                'label' => 'Confirm Arrival at Customer Location',
                'action' => 'confirm-arrival',
            ];
            // $action = [
            //     'label' => 'Confirm Arrival at Customer Location',
            //     'action' => 'confirm-arrival',
            // ];
        }
        elseif ((int) $this->booking_status_id === BookingStatus::STATUS_BOOKING_ARRIVAL_AT_CUSTOMER) {
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
        return match ((int) $this->order_status_id) {
            LibraryStatus::STATUS_ORDER_PLACED => [
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
            LibraryStatus::STATUS_ORDER_ACCEPTED, LibraryStatus::STATUS_PROCESSING => [
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
            LibraryStatus::STATUS_READY_FOR_PICKUP => [
                'label' => 'Waiting for Rider to Pickup',
                'button' => null,
                'cancel' => [
                    'label' => 'Cancel Order',
                    'action' => 'cancel',
                ],
                'send_to_rider' => false,
            ],
            LibraryStatus::STATUS_DELIVERED => [
                'label' => 'Order Delivered',
                'button' => null,
                'cancel' => null,
                'send_to_rider' => false,
            ],
            LibraryStatus::STATUS_CANCELLED => [
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

    public function agentCommission()
    {
         return $this->hasOne('App\AgentCommission', 'order_id');
    }

     public function user() 
    {
         return $this->hasOne('App\User', 'id', 'user_id');
    }

}
