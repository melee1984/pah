<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

use App\Partners;
use App\PartnerInquiry;
use App\Mail\MerchantResetPassword;
use App\User;
use App\Mail\PartnerMail;
use Carbon\Carbon;
use Hash;
use DB;
use Str;
use URL;
use Auth;

class PartnerController extends Controller
{
   	public function store(Request $request) 
   	{	
	    $validatedData = $request->validate([
			'restaurant_name' => 'required|max:75',
			'email' => 'required',
	        'contact_no' => 'required|max:75',
	        'address' => 'required|max:255',
	        'city' => 'required|max:30',
	    ]);
	    
	    $partners = PartnerInquiry::create($request->all());

		if ($partners) {

			$when = Carbon::now()->addMinutes(5);

			Mail::to('lparba@gmail.com')
				->bcc('leslie@artagile.com')
				->later($when, new PartnerMail($partners));

			$data['message'] = "You have successfully submitted your information. We will get back to you shortly. Thank you";
			$data['type'] = "success";
			$data['title'] = "Great!";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['type'] = "error";
			$data['title'] = "Sorry!";
			$data['status'] = 0;	
		}
		// $status = Mail::to($contactus->email)
		// 				->send(new ContactUsMail($contactus));	
	
		return response()->json($data, 200);
   	}

   	public function insertMerchantPartner(Request $request) 
   	{	
   		$data = array();

   		$validatedData = $request->validate([
			'restaurant_name' => 'required|max:75',
			'accountType' => 'required',
			'firstname' => 'required|max:75',
			'lastname' => 'required|max:75',
			'mobile' => 'required|max:25',
			'telephone' => 'required|max:25',
			'address' => 'required',
	        'city' => 'required|max:30',
			'email' => 'required',
			'password' => 'required',
	    ]);
		
		$user =  User::create([
            'firstname' =>$request->input('firstname'),
            'lastname' => $request->input('lastname'),
            'email' => $request->input('email'),
            'mobile' => $request->input('mobile'),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
			'api_token' => Str::random(80),
			'password' => Hash::make($request->input('password')),
        ]);

		if ($user) {
			// Creating Partner Data 
			// 
			
			$partners = Partners::create(['user_id' => $user->id,
					'restaurant_name' => $request->input('restaurant_name'),
					'slug' => Str::slug($request->input('restaurant_name'),'-'),
					'mobile' => $request->input('mobile'),
					'telephone' => $request->input('telephone'),
					'address' => $request->input('address'),
			        'city' => $request->input('city'),
					'email' => $request->input('email'),
					'facebook' => $request->input('facebook'),
					'status' => 1,
					'account_type_id'	=> $request->input('accountType'),
					'search_string' => Str::lower($request->input('restaurant_name')) . ", " . Str::lower($request->input('city')),
					'percentage' => 15,
					'addup' => 1,
					]
				
				);
			//
			// Settings up Partner Roles to access the merchant Dashboard page 
			$user->assignRole('partner');
			if ($partners) {
				// should send email notification 
				// welcome should I add this later when I approve the merchant partner 
			}

			$data['message'] = "Thank you for compiling the form. We will check and review your registration and send you the welcome message.";
			$data['status'] = 1;

		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		// $status = Mail::to($contactus->email)
		// 				->send(new ContactUsMail($contactus));	
	
		return response()->json($data, 200);
   	}

   	public function resetPassword(Request $request) 
   	{	
   		$data = array();

   		$validatedData = $request->validate([
			'email' => 'required|email',
	    ]);	

   		$user = DB::table('users')->where('email', '=', $request->email)
		    ->first();

		//Check if the user exists
		if ($user) {
		    $data['message'] = "The email address does not exist on our record.";
			$data['status'] = 0;
		}

		$token = Str::random(60);
	    //create Password Reset Token
		DB::table('password_resets')->insert([
		    'email' => $request->email,
		    'token' => $token,
		    'created_at' => Carbon::now()
		]);

		$data = array('token' => $token, 
			'url' =>  URL::to('merchant/reset-password/'.$token),
			'name' => $user->firstname);
	
		$when = Carbon::now()->addMinutes(1);

    	$sentEmail = Mail::to($user->email)
    		->later($when, new MerchantResetPassword($data));
		$sentEmail = true;

   		if ($sentEmail) {
			$data['message'] = "We have sent you the reset link to your registered email address.";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some error during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		// $status = Mail::to($contactus->email)
		// 				->send(new ContactUsMail($contactus));	
	
		return response()->json($data, 200);
   	}

   	public function storeOnlineUpdate(Request $request) {

   		$merchant = Auth::User()->merchant;
		$data['message'] = "Unable to process your request. Please refresh your page and again.";

   		if ($merchant) {
   			$merchant->store_open = $merchant->store_open?false:true;
   			$merchant->save();

   			$data['store_online'] = $merchant->store_open;
   			$data['status'] = 1;
   		}
   		else {
   			$data['status'] = 0;
   		}

   		return response()->json($data, 200);
   	}

}
