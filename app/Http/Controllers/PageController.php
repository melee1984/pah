<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Sector;
use Cache;
use App\Partners;

use App\Mail\ContactUsMail;
use Carbon\Carbon;
use Redirect;
use Mail;

class PageController extends Controller
{	
    public function index() {

        $sectors = Cache::remember('sector', 60 , function () {
            return Sector::whereIsFeatured(1)
                        ->whereActive(1)
                        ->orderBy('position', 'asc')
                        ->get();
        });

        $partners = Cache::remember('home.partner', 60 , function () {
                $partners = Partners::whereActive(1)
                        ->whereNotNull('verified_at')
                        ->orderBy('verified_at', 'asc')
                        ->take(9)
                        ->get();

                foreach($partners as $partner) {
                   $partner = $partner->imgCheck($partner);
                }

                return $partners;
        }); 

        return view('pages.home', compact('sectors', 'partners'));

    }
    public function aboutus() {

        return view('pages.about');
    }
    public function contactus() {

        return view('pages.contact');
    }
    public function privacypolicy() {

        return view('pages.privacypolicy');
    }
    public function termsofuse() {

        return view('pages.termsofuse');
    }
    public function fraudprevention() {

        return view('pages.fraudprevention');
    }
    public function paymentmethod() {

        return view('pages.payment');
    }
    public function bepartner() {

        return view('pages.bepartner');
    }

     /**
     * Newsletter
     * @param  Request $request [description]
     * @return redirect to the subscribe page 
     */
    public function storeContact(Request $request) {

        $validatedData = $request->validate([
            'name' => 'required',
            'email' => 'required',
            'subject' => 'required',
            'message' => 'required',
        ]);
        
        $data['name'] = $request->input('name');
        $data['email'] = $request->input('email');
        $data['subject'] = $request->input('subject');
        $data['message'] = $request->input('message');

        $when = Carbon::now()->addMinutes(1);

        $sentEmail = Mail::to('lparba@gmail.com')
                ->later($when, new ContactUsMail($data));

        return Redirect::back()->with('newslleter_status', 'true')->with('message','Thank you for sending us an email.');

    }


}
