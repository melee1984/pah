<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Sector;
use App\AccountType;
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

        $accountTypes = Cache::remember('home.account-types', 60, function () {
            return AccountType::orderBy('title')->get();
        });

        return view('pages.home', compact('sectors', 'partners', 'accountTypes'));

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

        return redirect()->to(route('home').'#become-a-partner');
    }

     /**
     * Newsletter
     * @param  Request $request [description]
     * @return redirect to the subscribe page 
     */
    public function storeContact(Request $request) {

        $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        return back()
            ->with('display', 'alert-success')
            ->with('message', 'Thank you for contacting Pahatud. We received your message.');
        
        $data['name'] = $request->input('name');
        $data['email'] = $request->input('email');
        $data['subject'] = $request->input('subject');
        $data['message'] = $request->input('message');

        $when = Carbon::now()->addMinutes(1);

        $sentEmail = Mail::to('lparba@gmail.com')
                ->later($when, new ContactUsMail($data));

        return Redirect::back()->with('newslleter_status', 'true')->with('message','Thank you for sending us an email.');

    }

    /** 
     * Demo Pages
     * 
     * 
     */
    public function demoMap() 
    {
        return view('pages.demo.map');  
    }

    public function restriction() 
    {
        return view('pages.location_restriction');  
    }   

}
