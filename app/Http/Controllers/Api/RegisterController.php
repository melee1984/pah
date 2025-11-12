<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use Illuminate\Support\Facades\Hash;


class RegisterController extends Controller
{	

   public function store(Request $request) 
   	{	
	    $validatedData = $request->validate([
			'firstname' => 'required|max:75',
			'lastname' => 'required|max:75',
	        'email' => 'required|max:75',
	        'mobile' => 'required|max:25',
	        'password' => 'required|max:30',
	    ]);

	    $user = User::create([
	    	'firstname' => $request->input('firstname'),
	    	'lastname' => $request->input('lastname'),
	    	'email' => $request->input('email'), 
	    	'mobile' => $request->input('mobile'),
	    	'password' => Hash::make('admin123'),
	    	'api_token' => Str::random(80),
			'ip_address' => \Request::ip()
		]);

	   if ($user) {
			
			$user->sendEmailVerificationNotification();
						
			$data['message'] = "We have sent you the email verification to your registered email address. Please check and confirm your registration. Thank you.";
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
   	
}
