<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocationController extends Controller
{
   /**
    * View 
    * @return [type] [description]
    */
    public function index() 
    {	
		return view('merchant.pages.location.view');        
    }
}
