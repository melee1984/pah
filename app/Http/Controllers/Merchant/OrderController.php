<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
     /**
    * View 
    * @return [type] [description]
    */
    public function index() 
    {	
		return view('merchant.pages.orders.view');        
    }
    /**
    * View 
    * @return [type] [description]
    */
    public function previous() 
    {	
		return view('merchant.pages.orders.view');        
    }
}
