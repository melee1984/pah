<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Auth;

class RequestController extends Controller
{
     /**
    * View 
    * @return [type] [description]
    */
    public function index() 
    {		
 		return view('pages.booking.view');
    }
    /**
     * [success description]
     * @return [type] [description]
     */
    public function success() {
    	return view('pages.booking.view');	
    }

    /**
     * 
     */
    public function bookings() {
        return view('pages.profile.bookings.view');          
    }
}
