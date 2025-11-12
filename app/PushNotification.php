<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{	
    //
     public static function sendPushToRider($order, $option = 1)
    {		

    	if ($order->rider->user)  {

    		if ($order->rider->user->device_token!="") {

    				$jobOrder = "0";
			    	$title = "Pahatud Food Delivery";

			    	if ($option == 1) {
			    		$body = "You got a new Job Order: " . $order->cart->order_no;
			    	}
			    	else {
			    		$body = "You got a new Job Order: " . $order->job_order;	
			    	}
			        
			        $firebaseToken = array($order->rider->user->device_token);
			        $SERVER_API_KEY = 'AAAAz9NPayk:APA91bEvqth-OJzcdM3_FJx7Kk_zG57CBXuyAy8BpM5AE6qtxSHwoux9LO5iQNfN1g9U_0fcv_yak17TgIH2XVKZFrdxKnEB4rpUGIufBlIK7WiXSYsSWJJqUei9PIRzubqjXlnRnWNG';

			        $data = [
			            "registration_ids" => $firebaseToken,
			            "notification" => [
			                "title" => $title,
			                "body" => $body,  
			            ]
			        ];

			        $dataString = json_encode($data);
			    
			        $headers = [
			            'Authorization: key=' . $SERVER_API_KEY,
			            'Content-Type: application/json',
			        ];
			    
			        $ch = curl_init();
			      
			        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
			        curl_setopt($ch, CURLOPT_POST, true);
			        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
			               
			        $response = curl_exec($ch);

    		}
    		
	  
	        // dd($response);

    	}
    	
    }

    public static function sendPushOrder($order, $option = 1) {	

    	if ($option == 1) {
    		// Order 
    		if (($order->status->id == 7) || ($order->status->id == 5) || ($order->status->id == 3)) {
	    		if ($order->user->device_token_food!="") {
					$jobOrder = "0";
			    	$title = "Pahatud Food Delivery";
			    	$body = "Job Order: " . $order->cart->order_no ." - ".  $order->status->description;
			        $SERVER_API_KEY = 'AAAAz9NPayk:APA91bEvqth-OJzcdM3_FJx7Kk_zG57CBXuyAy8BpM5AE6qtxSHwoux9LO5iQNfN1g9U_0fcv_yak17TgIH2XVKZFrdxKnEB4rpUGIufBlIK7WiXSYsSWJJqUei9PIRzubqjXlnRnWNG';


			        $firebaseToken = array($order->user->device_token_food);
			        $data = [
			            "registration_ids" => $firebaseToken,
			            "notification" => [
			                "title" => $title,
			                "body" => $body,  
			            ]
			        ];
			        $dataString = json_encode($data);
			        $headers = [
			            'Authorization: key=' . $SERVER_API_KEY,
			            'Content-Type: application/json',
			        ];
			        $ch = curl_init();
			        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
			        curl_setopt($ch, CURLOPT_POST, true);
			        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
			        return $response = curl_exec($ch);
	    		}
    		}
    	}
    	elseif ($option == 2) {

    		// Booking 
    		if (($order->status->id == 2) || ($order->status->id == 3) || ($order->status->id == 6)) {

	    		if ($order->user->device_token_food!="") {
					$jobOrder = "0";
			    	$title = "Pahatud Delivery";
			    	$body = "Job Order: " . $order->job_order ." - ".  $order->status->description;
			        $SERVER_API_KEY = 'AAAAz9NPayk:APA91bEvqth-OJzcdM3_FJx7Kk_zG57CBXuyAy8BpM5AE6qtxSHwoux9LO5iQNfN1g9U_0fcv_yak17TgIH2XVKZFrdxKnEB4rpUGIufBlIK7WiXSYsSWJJqUei9PIRzubqjXlnRnWNG';
			        $firebaseToken = array($order->user->device_token_food);
			        $data = [
			            "registration_ids" => $firebaseToken,
			            "notification" => [
			                "title" => $title,
			                "body" => $body,  
			            ]
			        ];
			        $dataString = json_encode($data);
			        $headers = [
			            'Authorization: key=' . $SERVER_API_KEY,
			            'Content-Type: application/json',
			        ];
			        $ch = curl_init();
			        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
			        curl_setopt($ch, CURLOPT_POST, true);
			        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
			        return $response = curl_exec($ch);
	    		}
    		}

    	}
    	
       
    }


    public static function sendPushStore($order)
    {		
    	if ($order->partner->partner_user->device_token_store!="") {

    		$jobOrder = "0";
	    	$title = "PahatudFood Delivery";
	        // $body = "You got a new Job Order: " . $jobOrder;
	        $body = "We have a new order for you Job Order " . $order->cart->order_no;

	        $SERVER_API_KEY = 'AAAAz9NPayk:APA91bEvqth-OJzcdM3_FJx7Kk_zG57CBXuyAy8BpM5AE6qtxSHwoux9LO5iQNfN1g9U_0fcv_yak17TgIH2XVKZFrdxKnEB4rpUGIufBlIK7WiXSYsSWJJqUei9PIRzubqjXlnRnWNG';

	        $firebaseToken = array($order->partner->partner_user->device_token_store);

	        $data = [
	            "registration_ids" => $firebaseToken,
	            "notification" => [
	                "title" => $title,
	                "body" => $body,  
	            ]
	        ];

	        $dataString = json_encode($data);
	    
	        $headers = [
	            'Authorization: key=' . $SERVER_API_KEY,
	            'Content-Type: application/json',
	        ];
	    
	        $ch = curl_init();
	      
	        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
	        curl_setopt($ch, CURLOPT_POST, true);
	        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
	               
	        $response = curl_exec($ch);

	        // dd($response);

    	}

    }

}
