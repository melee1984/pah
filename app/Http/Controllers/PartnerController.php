<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PartnerController extends Controller
{
	protected $casts = [
        'archive_at' => 'datetime',
        'verified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
