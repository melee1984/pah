<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Partners;
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

    public function tables(Request $request)
    {
        $merchant = Partners::where('user_id', $request->user()->id)->first();
        abort_unless($merchant, 403, 'A merchant account is required.');

        return view('merchant.pages.tables.view', [
            'qrOrderingEnabled' => (bool) $merchant->qr_ordering_enabled,
        ]);
    }
}
