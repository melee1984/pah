<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LibraryStatus extends Model
{
    protected $table = 'library_status';
	protected $fillable = array('title');
	public $timestamps = false;
}
