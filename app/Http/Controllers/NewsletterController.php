<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Redirect;

use App\Newsletter;

class NewsletterController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth');
    }
    /**
     * Newsletter
     * @param  Request $request [description]
     * @return redirect to the subscribe page 
     */
    public function subscribe(Request $request) {

        $validatedData = $request->validate([
            'email' => 'required',
        ]);
        
		$userBillsPayment = Newsletter::updateOrCreate(
                ['email' => $request->input('email')],
                ['email' => $request->input('email')]
            );

		return Redirect::back()->with('newslleter_status', 'true')->with('message','Thank for subscribing to our newsletter');

    }

}
