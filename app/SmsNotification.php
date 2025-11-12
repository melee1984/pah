<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SmsNotification extends Model
{
     //
    //
    public static function sendPaymentReceived() {

    	$ch = curl_init();

    	
    	$userMobile = "09162986547";
    	// 
    		
    	// $message = "We have received the amount of P2752 to our BPI Savings Account. We will proceed and process your bills Payment, Thank you.";
    	// 
    	$message = "Your payment to Davao Light with the amount of P235 has been processed and your payment will be posted within 3 business days. Ref No 00334, Thank you";
    	
    	// $message = "We've cancelled the transaction ref 00237 due to the wrong amount. Thank you.";
    	
    	// $message = "Your Payment to your Davao City Water District is completed, Thank you for using Findnora.";

		$parameters = array(
		    'apikey' => '2ca8701c1d35bb5406cac42db35d9835', //Your API KEY
		    'number' => $userMobile, 
		    'message' => $message,
		    'sendername' => 'PahatudFood'
		);
		
		curl_setopt( $ch, CURLOPT_URL,'https://semaphore.co/api/v4/messages' );
		curl_setopt( $ch, CURLOPT_POST, 1 );

		//Send the parameters set above with the request
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $parameters ) );

		// Receive response from server
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$output = curl_exec( $ch );
		curl_close ($ch);

		//Show the server response
		// echo $output;
 
    }

     public static function sendCompleteOrder($mobile, $code) {

     	// return; 
     	
    	$ch = curl_init();

    	$message = "Your One Time Pin (OTP) is " . $code . ". Please use this code to complete your order.";
    	
    	$parameters = array(
		    'apikey' => '2ca8701c1d35bb5406cac42db35d9835', //Your API KEY
		    'number' => $mobile, 
		    'message' => $message,
		    'sendername' => 'PahatudFood'
		);
		curl_setopt( $ch, CURLOPT_URL,'https://semaphore.co/api/v4/messages' );
		curl_setopt( $ch, CURLOPT_POST, 1 );

		//Send the parameters set above with the request
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $parameters ) );

		// Receive response from server
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$output = curl_exec( $ch );
		curl_close ($ch);

		//Show the server response
		// echo $output;
    }

    public static function sendSMS($message, $userMobile) {

    	$ch = curl_init();

    	$parameters = array(
		    'apikey' => '0f6a51a85d7e47b78d579224153f6c84', //Your API KEY
		    'number' => $userMobile, 
		    'message' => $message,
		    'sendername' => 'FINDNORA'
		);
		
		curl_setopt( $ch, CURLOPT_URL,'https://semaphore.co/api/v4/messages' );
		curl_setopt( $ch, CURLOPT_POST, 1 );

		//Send the parameters set above with the request
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $parameters ) );

		// Receive response from server
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$output = curl_exec( $ch );
		curl_close ($ch);

		//Show the server response
		// echo $output;

    }


     public static function sendNewOrder() {

    	$ch = curl_init();

    	$message = "Dashboard Update Trigger. Please check.";
    	
    	$parameters = array(
		    'apikey' => '2ca8701c1d35bb5406cac42db35d9835', //Your API KEY
		    'number' => '09162986547', 
		    'message' => $message,
		    'sendername' => 'PahatudFood'
		);
		curl_setopt( $ch, CURLOPT_URL,'https://semaphore.co/api/v4/messages' );
		curl_setopt( $ch, CURLOPT_POST, 1 );

		//Send the parameters set above with the request
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $parameters ) );

		// Receive response from server
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$output = curl_exec( $ch );
		curl_close ($ch);

		//Show the server response
		// echo $output;
    }
    
}
