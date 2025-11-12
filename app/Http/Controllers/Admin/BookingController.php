<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Index Dashboard Page 
     * @return [type] [description]
     */
    public function index() 
    {
    	return view('dashboard.pages.booking.add');
    }
}
