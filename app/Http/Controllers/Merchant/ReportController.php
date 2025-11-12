<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
    * View 
    * @return [type] [description]
    */
    public function today() 
    {	
		return view('merchant.pages.reports.today');        
    }

    /*
    * View 
    * @return [type] [description]
    */
    public function salesReport() 
    {	
		return view('merchant.pages.reports.salesReport');        
    }
    /*
    * View 
    * @return [type] [description]
    */
    public function soa() 
    {	
		return view('merchant.pages.reports.soa');        
    }
}
