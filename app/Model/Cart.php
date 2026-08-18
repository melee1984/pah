<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use DateTime;
use DateInterval;
use DatePeriod;
use Cache;

use DB;


use App\Model\DeliveryDistance;
use Symfony\Component\HttpFoundation\Session\Session;

class Cart extends Model
{
  protected $table = 'cart';
  protected $fillable = array('user_id', 'session_id', 'active', 'ip_address', 'partner_id', 'address_id', 'payment_id', 'user_long', 'user_lat', 'delivery_date', 'delivery_time', 'sms_code_validated_at', 'partner_location_address_id', 'delivery_fee', 'distance_rate', 'duration', 'origin', 'destination', 'discount_amount');
  protected $hidden = array('created_at', 'updated_at', 'processed_at', 'user_id', 'ip_address');

  public $timestamps = true;

  /**
   * Validate User if has Admin Roles 
   * @return boolean
   */
  public function details()
  {
    return $this->hasMany('App\Model\CartItem', 'cart_id', 'id')->with('item');
  }

  public function address()
  {
    return $this->hasOne('App\Model\CartUserAddress', 'cart_id', 'id');
  }

  public function payment()
  {
    return $this->hasOne('App\PaymentMethod', 'id', 'payment_id');
  }

  public function partner()
  {
    return $this->hasOne('App\Partners', 'id', 'partner_id');
  }

  public function order()
  {
    return $this->hasOne('App\Model\Orders\Orders', 'cart_id', 'id');
  }

  public function partnerlocation()
  {
    return $this->hasOne('App\PartnerLocation', 'id', 'partner_location_address_id');
  }

  /**
   * [cartSummary description]
   * @return [type] [description]
   */
  public function cartItemSummary()
  {

    $qty = "";
    $sub_total = 0;
    $delivery_fee = 0;
    $discount = 0;
    $total = 0;
    $qty = 0;
    $total_com = 0;

    $discountAmount = 0;
    $delivery_fee = $this->delivery_fee;
    $discountAmount = $this->discount_amount;

    foreach ($this->details as $item) {
      $qty = $qty + (int) $item->qty;
      $sub_total = $sub_total + (int) $item->qty * ((float) $item->price + (float) $item->variance_total);
      $total_com = $total_com + (int) $item->qty * ((float) $item->price_comm_total + (float) $item->variance_total_comm_total);

      $discountAmount = $discountAmount + (int) $item->qty * (float) $item->discount_amount;
    }

    // discountAmount = 0;

    $discount = $discountAmount;
    $total = ($sub_total + $delivery_fee) - $discount;

    $summary = array(
      'sub_total' => number_format($sub_total, 2),
      'delivery_fee' => number_format($delivery_fee, 2),
      'discount' => number_format($discount, 2),
      'total' => number_format($total, 2),
      'qty' => $qty,
      'total_comm' => number_format($total_com, 2),
      'currency' => "₱",
    );

    return $summary;
  }
  /**
   * [cartItemList description]
   * @return [type] [description]
   */
  public function cartItemList()
  {

    return $this->details;
  }

  public function cartItemVariance()
  {

    foreach ($this->details as $list) {

      $list->variance_content = unserialize($list->variance_content);

      if ($list->item) {
        // I can call this because I am using hasOne 
        $list->item->price = $list->item->getPrice() + $list->variance_total;
      }
    }

    return $this->details;
  }

  /**
   * [getTotalCartForToday description]
   * @return [type] [description]
   */
  public function generateOrderNo()
  {

    $order = Cart::count();
    $length = 6;
    return $orderNo = substr(str_repeat(0, $length) . "82" . $order, -$length);
  }
  /**
   * [getTimingsSchedule description]
   * @return [type] [description]
   */
  // public function getTimingsSchedule()
  // {

  //   $data = array();
  //   $begin = new DateTime(date('H:m', time()));
  //   $end = new DateTime("19:00");

  //   array_push($data, array('time' => 'ASAP'));
  //   $interval = DateInterval::createFromDateString('45 min');

  //   $times = new DatePeriod($begin, $interval, $end);

  //   foreach ($times as $time) {
  //     array_push($data, array('time' => $time->add($interval)->format('M d Y - g:i A')));
  //   }

  //   return $data;
  // }

 public function getTimingsSchedule(
    $days = 2, 
    $customDurationMinutes = 30, 
    $customStartDate = null, 
    $dayStartTime = '08:00', 
    $dayEndTime = '23:00',
    $intervalMinutes = 45
) {
    $data = [];

    // Determine start date
    $startDate = $customStartDate 
        ? new DateTime($customStartDate) 
        : new DateTime(); // default: today

    $now = new DateTime(); // current time for comparison

    for ($i = 0; $i < $days; $i++) {
        $date = clone $startDate;
        $date->modify("+$i day");
        $dateString = $date->format('F d, Y');

        $times = [];

        // Determine start time
        if ($i === 0) {
            // First day: current time + custom duration
            $begin = clone $now;
            $begin->add(new DateInterval("PT{$customDurationMinutes}M"));

            // Round up to the next interval
            $minutes = (int)$begin->format('i');
            $remainder = $intervalMinutes - ($minutes % $intervalMinutes);
            if ($remainder != $intervalMinutes) {
                $begin->add(new DateInterval("PT{$remainder}M"));
            }

            // Add ASAP as the first slot
            $times[] = [
                'time' => 'ASAP',
                'disabled' => false
            ];
        } else {
            // Other days: fixed start time
            $begin = DateTime::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . " $dayStartTime");
        }

        // End time for the day
        $end = DateTime::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . " $dayEndTime");

        $interval = new DateInterval("PT{$intervalMinutes}M");
        $period = new DatePeriod($begin, $interval, $end);

        foreach ($period as $time) {
            // Compare with current time only for today
            $disabled = false;
            if ($i === 0 && $time < $now) {
                $disabled = true;
            }

            $times[] = [
                'time' => $time->format('g:i A'),
                'disabled' => $disabled
            ];
        }

        $data[] = [
            'date'  => $dateString,
            'times' => $times,
        ];
    }

    return $data;
}

  /**
   * Validate and get the current delivery for this cart 
   * @return [type] [description]
   */
  public function deliveryRate()
  {

    try {

      if (!$this->partner)
        return;

      // recheck the number of partner available 
      // and if multiple then validate asa ang pinaka duol 

      $nearest_store_location = DB::table('partner_location')
        ->select('id', 'latitude', 'longtitude', 'address_1', DB::raw('
                 (select ST_Distance_Sphere(
                  point(partner_location.longtitude, partner_location.latitude),
                  point(' . $this->user_long . ', ' . $this->user_lat . ')) * 0.001 / 1000)  as meter'))
        ->whereActive(1)
        ->wherePartnerId($this->partner->id)
        ->orderby('meter', 'asc')
        ->first();

      if (!$nearest_store_location) {
        return false;
      }

      // Saving location of the partner 
      // $this->partner_location_address_id = $nearest_store_location->id; // removing this since this doesn't require anymore. 
      // we have this partner_location_address_id sa cart table. so we can use that instead of this.
      // $part_coordinate = $this->partner->location->latitude.",".$this->partner->location->longtitude;
      $part_coordinate = $nearest_store_location->latitude . "," . $nearest_store_location->longtitude;
    // mau coordinate niiya sa user 
      $user_coordinate = $this->user_lat . "," . $this->user_long;

      // \Log::info('Computing delivery rate for cart', [
      //   'cart_id' => $this->id,
      //   'partner_id' => $this->partner->id,
      //   'user_coordinate' => $user_coordinate,
      //   'partner_coordinate' => $part_coordinate,
      // ]);

      // \Log::info(['Partner/Merchant/Restuarnat Information' => $nearest_store_location]);
      // \Log::info(['Cart Information' => $this]);

      $data = DeliveryDistance::getCoordinateComputation($part_coordinate, $user_coordinate);

      // \Log::info(['delivery_rate_data' => $data]);
      // \Log::info(['Nearest Partner Location' => $nearest_store_location]);

      //
      $delivery_fee = str_replace(',', '', (string) ($data['rate'] ?? 0));
      $this->delivery_fee = is_numeric($delivery_fee) ? round((float) $delivery_fee, 2) : 0;
      $this->distance_rate = $data['distance'] ?? 0;
      $this->duration = $data['duration'] ?? '';
      $this->origin = $data['origin'] ?? '';
      $this->destination = $data['destination'] ?? '';

      // $this->delivery_fee = 50;
      // $this->distance_rate = "7KM";
      // $this->duration = "10mins";
      // $this->origin = "";
      // $this->destination = "";

      // Check if with special features for the delivery fee;
      $this->validateDeliveryFee();

      return $this->save();

    } catch (\Throwable $e) {

      \Log::error('Error during delivery rate computation. ' . $e->getMessage());

      return false;
    }
  }

  public function validateDeliveryFee()
  {

    if ($this->partner->account_type_id == 4) {
      if ($this->partner->no_delivery_fee) {
        $this->delivery_fee = 0;
      }
    }

  }

  public function cartItemTotal()
  {

    $qty = "";
    $sub_total = 0;
    $delivery_fee = 0;
    $discount = 0;
    $total = 0;
    $qty = 0;

    $discountAmount = 0;

    $delivery_fee = $this->delivery_fee;
    $discountAmount = $this->discount_amount;

    foreach ($this->details as $item) {

      $qty = $qty + (int) $item->qty;
      $sub_total = $sub_total + (int) $item->qty * ((float) $item->price + (float) $item->variance_total);

      $discountAmount = $discountAmount + (int) $item->qty * (float) $item->discount_amount;

    }

    // discountAmount = 0;

    $discount = $discountAmount;
    $total = ($sub_total + $delivery_fee) - $discount;

    // $summary = array(
    //     'sub_total' => number_format($sub_total, 2),
    //     'delivery_fee' => number_format($delivery_fee,2),
    //     'discount' => number_format($discount,2),
    //     'total' => number_format($total, 2),
    //     'qty' => $qty,
    //   );

    return $total;
  }

  public function isEmpty(): bool
  {
     $session_id = session()->getId();
     // Retrieve cart for current session
      $cart = Cart::where('session_id', $session_id)->first();

      // If no cart exists or it has no items, return true
      if (!$cart || $cart->cartItemList()->count() === 0) {
          return true;
      }

      return false;
  }

}
