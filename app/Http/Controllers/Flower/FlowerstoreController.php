<?php

namespace App\Http\Controllers\Flower;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FlowerstoreController extends Controller
{
    public function show() {

        return view('pages.flower.view');
    }
}
