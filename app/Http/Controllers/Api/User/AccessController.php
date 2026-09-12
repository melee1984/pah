<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
      'password'=>'required|min:6'
    ];

    $session_id = Session::getId();
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      // Validation failed
      return response()->json([
      'status' => 0,
      'message' => "The username and password you've entered might be incorrect. Please try again.",
      'errors' => $validator->errors(), // remove this after debugging
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
              'is_with_sms_otp' => true
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
   * Sign in or register a mobile user with a Google ID token.
   */
  public function google(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'id_token' => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'message' => 'A Google ID token is required.',
        'errors' => $validator->errors(),
      ], 422);
    }

    $clientIds = config('services.google.client_ids', []);

    if (empty($clientIds)) {
      return response()->json([
        'status' => 0,
        'message' => 'Google Sign-In is not configured.',
      ], 503);
    }

    try {
      $googleResponse = Http::acceptJson()
        ->timeout(10)
        ->get('https://oauth2.googleapis.com/tokeninfo', [
          'id_token' => $request->input('id_token'),
        ]);
    } catch (\Throwable $exception) {
      report($exception);

      return response()->json([
        'status' => 0,
        'message' => 'Google Sign-In is temporarily unavailable.',
      ], 503);
    }

    $googleUser = $googleResponse->json();
    $issuer = $googleUser['iss'] ?? null;
    $emailVerified = filter_var(
      $googleUser['email_verified'] ?? false,
      FILTER_VALIDATE_BOOLEAN
    );

    if (
      !$googleResponse->successful()
      || !in_array($googleUser['aud'] ?? null, $clientIds, true)
      || !in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)
      || empty($googleUser['sub'])
      || empty($googleUser['email'])
      || !$emailVerified
      || (int) ($googleUser['exp'] ?? 0) <= now()->timestamp
    ) {
      return response()->json([
        'status' => 0,
        'message' => 'The Google ID token is invalid or expired.',
      ], 401);
    }

    $user = User::where('provider', 'google')
      ->where('provider_id', $googleUser['sub'])
      ->first();

    if (!$user) {
      $user = User::where('email', $googleUser['email'])->first();
    }

    if ($user && $user->provider && $user->provider !== 'google') {
      return response()->json([
        'status' => 0,
        'message' => 'This email is already linked to another sign-in provider.',
      ], 409);
    }

    $isNewUser = !$user;
    $user = $user ?: new User;
    $user->firstname = $googleUser['given_name']
      ?? $googleUser['name']
      ?? strstr($googleUser['email'], '@', true);
    $user->lastname = $googleUser['family_name'] ?? ($user->lastname ?: '');
    $user->email = $googleUser['email'];
    $user->avatar = $googleUser['picture'] ?? $user->avatar;
    $user->password = $user->password ?: '';
    $user->provider = 'google';
    $user->provider_id = $googleUser['sub'];
    $user->ip_address = $request->ip();
    $user->api_token = $this->apiToken;
    $user->email_verified_at = $user->email_verified_at ?: now();
    $user->save();

    Auth::login($user);

    $sessionId = Session::getId();
    $cart = Cart::firstOrCreate([
      'session_id' => $sessionId,
      'ip_address' => $request->ip(),
    ]);
    $cart->user_id = $user->id;
    $cart->save();

    return response()->json([
      'status' => 1,
      'name' => trim($user->firstname.' '.$user->lastname),
      'firstname' => $user->firstname,
      'lastname' => $user->lastname,
      'email' => $user->email,
      'currency' => 'PHP',
      'access_token' => $this->apiToken,
      'mobile' => $user->mobile,
      'session_id' => $sessionId,
      'photo' => $user->avatar,
      'is_new_user' => $isNewUser,
    ]);
  }

  /**
   * Client Login
   */
  public function loginAccess(Request $request)
  {
      // 1. Validate input
      $credentials = $request->validate([
          'email'    => 'required|email',
          'password' => 'required',
      ]);

      $remember = $request->boolean('remember');

      // 2. Attempt login
      if (!Auth::attempt($credentials, $remember)) {
          return back()
              ->with('display', 'alert-danger')
              ->with('message', 'Your email or password is incorrect.')
              ->withInput();
      }

      // 3. Merge session cart to logged-in user
      $this->mergeCart(Session::getId(), Auth::id());

      // 4. Redirect to intended page or homepage
      return redirect()->intended('/');
  }

  /**
   * Merge session cart to logged-in user
   */
  protected function mergeCart(string $sessionId, int $userId): void
  {
      $cart = Cart::where('session_id', $sessionId)->first();

      if ($cart) {
          $cart->update(['user_id' => $userId]);
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
      'firstname' => 'required',
      'lastname'  => 'required',
      'email'     => 'required|email|unique:users,email',
      'mobile'    => 'required',
      'password'  => 'required|min:5|confirmed',
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
      $user->firstname = $request->input('firstname');
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
    $request->user()->forceFill([
      'api_token' => null,
    ])->save();

    return response()->json([
      'status' => 1,
      'message' => 'User logged out successfully.',
    ]);
  }


  public function refreshUser(Request $request) {
      return response()->json($request->user()->pullUserInfo(), 200);

  }

  /**
   * Client Login / User Login 
   */
  public function loginStore(Request $request)
  { 
    // Validations
    $rules = [
      'email'=>'required|email',
      'password'=>'required|min:6'
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
              'is_with_sms_otp' => true
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
