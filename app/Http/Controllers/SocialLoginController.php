<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Socialite;
use App\User;
use Auth;
use Str;

class SocialLoginController extends Controller
{
    //
    //
    public function loginFB() {
    	echo "Login FB Account";
    	die();
    }
    /**
     * Get Social Redirect 
     * @param  [type] $account [description]
     * @return [type]          [description]
     */
    public function getSocialRedirect( $account )
    {
      try {
        return Socialite::with( $account )->redirect();
      }catch ( \InvalidArgumentException $e ){
        return redirect('/login');
      }
    }

    public function getSocialCallback( $account ){

    	try {
           
           $socialUser = Socialite::with( $account )->user();

        } catch (\Exception $e) {
            return redirect('/login');
        }
		$user = User::where( 'provider_id', '=', $socialUser->id )
		  		->where( 'provider', '=', $account )
		        ->first();
		/*
			Checks to see if a user exists. If not we need to create the
			user in the database before logging them in.
		*/

		$existingUser = User::where('email', $socialUser->email)->first();

		// return redirect('/sign-in/fb/complete')
		// 						->with('firstname', $socialUser->getName())
		// 						->with('email', $socialUser->getEmail())
		// 						->with('provider_id', $socialUser->getId())
		// 						->with('avatar', $socialUser->getAvatar())
		// 						->with('provider', $account);

		// die();
		
		if($existingUser){
		    // log them in
		    
		   	$existingUser->firstname   = $socialUser->getName();
		    $existingUser->email       = $socialUser->getEmail() == '' ? '' : $socialUser->getEmail();
		    $existingUser->avatar      = $socialUser->getAvatar();
		    $existingUser->password    = '';
		    $existingUser->provider    = $account;
		    $existingUser->provider_id = $socialUser->getId();
		    $existingUser->ip_address  = $_SERVER['REMOTE_ADDR'];
		    $existingUser->api_token  =  Str::random(80);
		    $existingUser->save();
		    
		    Auth::login($existingUser, true);

		    return redirect('/');
		} 

		else {

			// check here if the email address has values 
			// 
			// 

			if ($socialUser->getEmail() == "") {
				
				return redirect('/sign-in/fb/complete')
							->with('firstname', $socialUser->getName())
							->with('email', $socialUser->getEmail())
							->with('provider_id', $socialUser->getId())
							->with('avatar', $socialUser->getAvatar())
							->with('provider', $account);

			}
			else {

				$newUser = new User();
			    $newUser->firstname   = $socialUser->getName();
			    $newUser->email       = $socialUser->getEmail() == '' ? '' : $socialUser->getEmail();
			    $newUser->avatar      = $socialUser->getAvatar();
			    $newUser->password    = '';
			    $newUser->provider    = $account;
			    $newUser->provider_id = $socialUser->getId();
			    $newUser->ip_address  = $_SERVER['REMOTE_ADDR'];
			    $newUser->api_token  =  Str::random(80);
			    $newUser->save();
			    $user = $newUser;
			    Auth::login( $user, true);
			}

 		    
		}

		if (Auth::check()) {
			return redirect('/');
		}
		else {
			return redirect('/login');
		}

    }	

}