<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Model\Orders\Orders;

use App\Events\SendPushNotificationEvent;

class PushNotificationController extends Controller
{
    public function sendNotification(Request $request)
    {	
    	$jobOrder = "0";
    	$title = "PahatudFood";
        // $body = "You got a new Job Order: " . $jobOrder;
        $body = "TEST Push Notification";

        // $firebaseToken = User::whereNotNull('device_token')
        // 	->pluck('device_token')->all();

        	// dd($firebaseToken);
          
        // $SERVER_API_KEY = 'AAAAz9NPayk:APA91bEvqth-OJzcdM3_FJx7Kk_zG57CBXuyAy8BpM5AE6qtxSHwoux9LO5iQNfN1g9U_0fcv_yak17TgIH2XVKZFrdxKnEB4rpUGIufBlIK7WiXSYsSWJJqUei9PIRzubqjXlnRnWNG'; Rider 
        // 
        $SERVER_API_KEY = 'AAAAz9NPayk:APA91bEvqth-OJzcdM3_FJx7Kk_zG57CBXuyAy8BpM5AE6qtxSHwoux9LO5iQNfN1g9U_0fcv_yak17TgIH2XVKZFrdxKnEB4rpUGIufBlIK7WiXSYsSWJJqUei9PIRzubqjXlnRnWNG';
        
        $device_token = array('edLBVb3CQ_SZtt1__rST3M:APA91bFE617NCn9rlGITPzCRulcQe-GJDX6pu1D6OGEEheheUJkcl4l0BJ382HIgdzb7E4qNle6-mz89mOQqXs5FRqkf6mwSNfJpMpsH-5uNu-VygKs2viCc-rqCbzV-jGmPtocJKxKf');

        $data = [
            "registration_ids" => $device_token,
            "notification" => [
                "title" => $title,
                "body" => $body,  
                "sound" => "sound",
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
  
        dd($response);
    }

    public function eventPush(Orders $order) {
        event (new SendPushNotificationEvent($order));
    }


}
