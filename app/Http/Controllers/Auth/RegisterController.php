<?php

namespace App\Http\Controllers\Auth;

use App\City;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Str;

class RegisterController extends Controller implements HasMiddleware
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public static function middleware(): array
    {
        return [
            new Middleware('turnstile:customer_register', only: ['register']),
        ];
    }

    public function showRegistrationForm(): View
    {
        return view('auth.register', [
            'cities' => City::query()->active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:255'],
            'city' => [
                'required',
                'string',
                'max:120',
                Rule::exists('cities', 'name')->where('active', true),
            ],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'city.exists' => 'Select an active city.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return \App\User
     */
    protected function create(array $data)
    {
        return User::create([
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
            'mobile' => $data['mobile'],
            'city' => $data['city'],
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'api_token' => Str::random(80),
            'password' => Hash::make($data['password']),
        ]);
    }
}
