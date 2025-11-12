<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LibraryBudget extends Model
{
    protected $table = 'library_budget';
	protected $fillable = array('title');
	public $timestamps = false;

}
