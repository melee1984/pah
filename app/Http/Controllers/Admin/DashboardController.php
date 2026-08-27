<?php

namespace App\Http\Controllers\Admin;

use App\Agent;
use App\AgentCommission;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use User;

use Auth;
class DashboardController extends Controller
{	
	/**
     * Login Merchant Account
     * @return view 
     */
    public function login() 
	{
		return view('dashboard.pages.login');
    }
    public function logout(Request $request) 
	{
	  Auth::logout();
	  return redirect('/data/login');
	}
    public function validateLogin(Request $request) 
	{	

		$request->validate( [
            'email' => 'required|email',
            'password' => 'required',
        ]);

	    $remember_me = $request->has('remember') ? true : false; 

	    if (auth()->attempt(['email' => $request->input('email'), 'password' => $request->input('password')], $remember_me))
	    {
	         $user = auth()->user();
	         if ($user->isAdmin()) {
	         	return redirect()->intended('data/dashboard');   	
	         }
	         else {
	        	return back()
	        	->with('display', 'alert-danger')
	        	->with('message','The access you have does not have role to access Admin partner dashboard. Please contact system administrator to fix this.')
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

   /**
     * Index Dashboard Page 
     * @return [type] [description]
     */
    public function index() 
    {
        $agentMetrics = [
            'total' => Agent::query()->count(),
            'active' => Agent::query()->where('active', true)->count(),
            'setup_pending' => Agent::query()->where('must_change_password', true)->count(),
            'commission' => (float) AgentCommission::query()->earned()->sum('commission_amount'),
        ];

        return view('dashboard.pages.main', compact('agentMetrics'));
    }

    public function reportOrder() {
    	return view('dashboard.pages.report.orders');	
    }

    public function memberList() {
    	return view('dashboard.pages.user.member');		
    }

    public function merchantlist() {
    	return view('dashboard.pages.user.merchant');		
    }

}
