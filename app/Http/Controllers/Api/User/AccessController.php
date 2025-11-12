<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Str;
use Validator;
use Hash;
use App\User;
use App\UserVerificationMobile;
use App\NotificationAction;
use Session;
use Auth;
use URL;
use App\Model\Cart;

class AccessController extends Controller
{
    private $apiToken;
	public function __construct()
	{
		// Unique Token
		$this->apiToken = uniqid(base64_encode(Str::random(60)));
	}

  /**
   * Update the authenticated user's API token.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function update(Request $request)
  {
      $token = Str::random(60);
      $request->user()->forceFill([
          'api_token' => hash('sha256', $token),
      ])->save();

      return ['token' => $token];
  }

  /**
   * Client Login
   */
  public function login(Request $request)
  { 
    // Validations
    $rules = [
      'email'=>'required|email',
      'password'=>'required|min:8'
    ];

    $session_id = Session::getId();

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      // Validation failed
      return response()->json([
        'status' => 0,
        'message' => "The username and password you've enter might be incorrect. Please try again."
      ]);
      
    } else {
      // Fetch User
      $user = User::where('email',$request->email)->first();
      
      if($user) {

        // Verify the password
        if( password_verify($request->password, $user->password) ) {

          Auth::login($user);
          Session::setId($session_id);

          // updating cart to avoid issue 
          // $cart = Cart::whereSessionId($session_id)
          //         ->first();

          $cart = Cart::firstOrCreate([
              'session_id' => $session_id,
              'ip_address' => $_SERVER['REMOTE_ADDR'],
          ]);


          if ($cart) {
            $cart->user_id = $user->id;
            $cart->save();  
          }
          
          // Update Token
          $postArray = ['api_token' => $this->apiToken];
          $login = User::where('email',$request->email)->update($postArray);

          if($login) {
            return response()->json([
              'status'    =>  1,
              'name'         => $user->firstname . " " . $user->lastname,
              'firstname'    => $user->firstname,
              'lastname'    => $user->lastname,
              'email'        => $user->email,
              'mobile'  =>$user->mobile,
              'access_token' => $this->apiToken,
              'session_id' => $session_id,
              'redirectURL' =>  URL::to($request->input('page')),

            ]);
          }
        } else {
          return response()->json([
            'message' => 'Invalid Password',
          ]);
        }
      } else {
        return response()->json([
          'message' => 'User not found',
        ]);
      }
    }

  }

  /**
   * Client Login
   */
  public function loginAccess(Request $request)
  { 

    $session_id = Session::getId();

    $this->validate($request, [
          'email' => 'required|email',
          'password' => 'required',
      ]);

      $remember_me = $request->has('remember') ? true : false; 

      if (auth()->attempt(['email' => $request->input('email'), 'password' => $request->input('password')], $remember_me))
      {
          Session::setId($session_id);

          $cart = Cart::whereSessionId($session_id)->first();

          if ($cart) {
            $cart->user_id = Auth::User()->id;
            $cart->save();  
          }

          return redirect()->back();
      }
      else{
          return back()
            ->with('display', 'alert-danger')
            ->with('message','your username and password are wrong.')
            ->withInput();
      }
  }


  public function registermobile(Request $request) {

  // Validations
    $rules = [
      'mobile'     => 'required',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      // Validation failed
      return response()->json([
        'message' => $validator->messages(),
      ]);

    } else {

      $sms_code = mt_rand(10,10000);

      $user = new UserVerificationMobile;
      $user->mobile = $request->input('mobile');
      $user->code = $sms_code;
      $user->access_token = $this->apiToken;
      $status = $user->save();

      if($user) {

        NotificationAction::sendRegistrationCode($user->mobile, $user->sms_code);

        return response()->json([
          'status'  =>  $status,
          'mobile'    => $user->mobile,
          'code'      => $user->code,
          'access_token' => $this->apiToken,
        ]);
      } else {
        return response()->json([
            'message' => 'Registration failed, please try again.',
        ]);
      }
    }
  }

  public function registerFB()
  {
      return view('auth.completefb');
  }

  public function registerMobile2(Request $request)
  {
    
    // Validations
    $rules = [
      'fullname'     => 'required',
      'email'    => 'required|unique:users,email',
      'mobile'    => 'required',
      'password' => 'required|min:5'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      // Validation failed
      // return response()->json([
      //   'message' => $validator->messages(),
      // ]);
      // 
      $data['message'] = $validator->messages()->first();

       return response()->json([
        'message' => $validator->messages()->first(),
      ]);
      

    } else {

      $user = new User;
      $user->firstname = $request->input('fullname');
      $user->email = $request->input('email');
      $user->mobile = $request->input('mobile');
      $user->password =  Hash::make($request->input('password'));
      $user->api_token = $this->apiToken;
      $user->save();

      if($user) {

         $session_id = Session::getId();
          
          Session::setId($session_id);

          $cart = Cart::firstOrCreate([
              'session_id' => $session_id,
              'ip_address' => $_SERVER['REMOTE_ADDR'],
          ]);

          if ($cart) {
              $cart->user_id = $user->id;
              $cart->save();  
          }

        return response()->json([
            'name'         => $user->firstname . " " . $user->lastname,
            'firstname'    => $user->firstname,
            'lastname'    => $user->lastname,
            'email'        => $user->email,
            'currency'        => 'PHP',
            'access_token' => $this->apiToken,
            'mobile' => $user->mobile,
            'session_id' => $session_id,
            'photo' => $user->avatar

        ]);
      } else {
        return response()->json([
            'message' => 'Registration failed, please try again.',
        ]);
      }
    }
  }

  /**
   * Register
   */
  public function register(Request $request)
  {
    // Validations
    $rules = [
      'firstname'     => 'required',
      'lastname'     => 'required',
      'email'    => 'required|unique:users,email',
      'mobile'    => 'required',
      'password' => 'required|min:5'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      // Validation failed
      return response()->json([
        'message' => $validator->messages(),
      ]);
    } else {

      $user = new User;
      $user->lastname = $request->input('lastname');
      $user->firstname = $request->input('fullname');
      $user->email = $request->input('email');
      $user->mobile = $request->input('mobile');
      $user->password =  Hash::make($request->input('password'));
      $user->api_token = $this->apiToken;
      $user->save();

      if($user) {

         $session_id = Session::getId();
          
          Session::setId($session_id);

          $cart = Cart::firstOrCreate([
              'session_id' => $session_id,
              'ip_address' => $_SERVER['REMOTE_ADDR'],
          ]);

          if ($cart) {
              $cart->user_id = $user->id;
              $cart->save();  
          }

        return response()->json([
            'name'         => $user->firstname . " " . $user->lastname,
            'firstname'    => $user->firstname,
            'lastname'    => $user->lastname,
            'email'        => $user->email,
            'currency'        => 'PHP',
            'access_token' => $this->apiToken,
            'mobile' => $user->mobile,
            'session_id' => $session_id,
            'photo' => $user->avatar

        ]);
      } else {
        return response()->json([
            'message' => 'Registration failed, please try again.',
        ]);
      }
    }
  }
  /**
   * Logout
   */
  public function postLogout(Request $request)
  {
    $token = $request->header('Authorization');
    $user = User::where('api_token',$token)->first();
    if($user) {
      $postArray = ['api_token' => null];
      $logout = User::where('id',$user->id)->update($postArray);
      if($logout) {
        return response()->json([
          'message' => 'User Logged Out',
        ]);
      }
    } else {
      return response()->json([
        'message' => 'User not found',
      ]);
    }
  }


  public function refreshUser(Request $request) {
      return response()->json($request->user()->pullUserInfo(), 200);

  }

  /**
   * Client Login
   */
  public function loginStore(Request $request)
  { 
    // Validations
    $rules = [
      'email'=>'required|email',
      'password'=>'required|min:8'
    ];

    $session_id = Session::getId();

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      // Validation failed
      return response()->json([
        'status' => 0,
        'message' => "The username and password you've enter might be incorrect. Please try again."
      ]);
      
    } else {
      // Fetch User
      $user = User::where('email',$request->email)->first();
      
      if($user) {
        // Verify the password
        if(password_verify($request->password, $user->password) ) {

          Auth::login($user);

          if (!$user->isMerchant()) {
              return response()->json([
                'status' => 0,
                'message' => "The account that you are trying to login does not have permission to enter. Please contact administrator.",
              ]);

          }

          Session::setId($session_id);

          // Update Token
          $postArray = ['api_token' => $this->apiToken];
          $login = User::where('email',$request->email)->update($postArray);

          if($login) {
            return response()->json([
              'status'    =>  1,
              'name'         => $user->firstname . " " . $user->lastname,
              'firstname'    => $user->firstname,
              'lastname'    => $user->lastname,
              'email'        => $user->email,
              'mobile'  =>$user->mobile,
              'access_token' => $this->apiToken,
              'session_id' => $session_id,
              'redirectURL' =>  URL::to($request->input('page')),
              'store' => $user->merchant,

            ]);
          }
        } else {
          return response()->json([
            'message' => 'Invalid Password',
          ]);
        }
      } else {
        return response()->json([
          'message' => 'User not found',
        ]);
      }
    }

  }

}
