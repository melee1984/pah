<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use DB;
use App\User;
use Hash;

use App\AccountType;
use Cache;

class DashboardController extends Controller
{
    
	/**
     * Login Merchant Account
     * @return view 
     */
    public function login() {
		  return view('merchant.pages.login');
    }
    public function register() {

        $accountType = Cache::remember('sector', 60 , function () {
            return AccountType::get();
        });

        return view('merchant.pages.register', compact('accountType'));

    }
    public function forgot(Request $request) {
		return view('merchant.pages.forgot');
    }
    public function logout(Request $request) {
	   Auth::logout();
  	  return redirect('/merchant/login');
  	}
  	public function verification() {
     
    	return view('merchant.pages.verify');
    }

    public function validateLogin(Request $request) {

    	$this->validate($request, [
	        'email' => 'required|email',
	        'password' => 'required',
	    ]);

	    $remember_me = $request->has('remember') ? true : false; 

	    if (auth()->attempt(['email' => $request->input('email'), 'password' => $request->input('password')], $remember_me))
	    {
	         $user = auth()->user();
	         if ($user->isMerchant()) {
				      return redirect()->intended('merchant/dashboard');	
	         }
	         else {
  	        	return back()
  	        	->with('display', 'alert-danger')
  	        	->with('message','The access you have does not have role to access Merchant partner dashboard. Please contact system administrator to fix this.')
  	        	->withInput(); 	
	         }
	    }
	    else{
	        return back()
	        	->with('display', 'alert-danger')
	        	->with('message','your username and password are wrong.')
	        	->withInput();
	    }

   	}	

   	public function showPasswordResetForm($token, Request $request) {

   		$reset_password_exist = DB::table('password_resets')->where('token', '=', $token)->first();

   		if ($reset_password_exist) {
   			return view('merchant.pages.reset', ['token' => $reset_password_exist->token ] );
   		}
   		else {
			return redirect(route('merchant.reset'))->with('display', 'alert-danger')->with('message','Token got expired. Please try again.');
   		}
   		
   	}

   	public function reset(Request $request) {
    	
    	$request->validate([
    	  'token' => 'required',
          'password' => 'required|string|min:6|confirmed',
          'password_confirmation' => 'required',
      	]);

    	$token =  DB::table('password_resets')->where('token', '=', $request->token)->first();

    	$user = User::where('email', '=', $token->email)->first();

    	if ($user) {
    		$user->fill(['password' => Hash::make($request->password)])->save();
    		
    		// Delete Password Reset     	
    		DB::table('password_resets')->where('token', '=', $request->token)->delete();
			return redirect(route('merchant.login'))->with('display', 'alert-success')->with('message','Successfully reset password.');
    	}
    	else {
    		return redirect(route('merchant.reset'))->with('display', 'alert-danger')->with('message','We have not found the user account. Please contact system administrator.');

    	}

    }

    /**
     * Index Dashboard Page 
     * @return [type] [description]
     */
    public function index() 
    {
     	return view('merchant.pages.dashboard');
    }

}
